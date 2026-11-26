<?php

namespace App\Controller;

use App\Entity\ImportOrder;
use App\Entity\Quote;
use App\Entity\QuoteStatusHistory;
use App\Repository\ImportOrderRepository;
use App\Repository\QuoteRepository;
use App\Service\ImportOrderTrackerService;
use App\Service\PapiPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Réception des notifications (callbacks) de paiement Papi.
 *
 * @see https://docs.papi.mg/docs/developper-guide/securing-notifications
 */
class PapiNotificationController extends AbstractController
{
    public function __construct(
        private PapiPaymentService $papiPaymentService,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/webhook/papi-notification', name: 'app_papi_notification', methods: ['POST'])]
    public function notify(
        Request $request,
        ImportOrderRepository $importOrderRepository,
        ImportOrderTrackerService $importOrderTrackerService,
        QuoteRepository $quoteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $rawBody = $request->getContent();
        $signatureHeader = $request->headers->get('X-Papi-Signature');

        if (!$this->papiPaymentService->verifyNotificationSignature($rawBody, $signatureHeader)) {
            $this->logger->warning('Papi: notification rejetée (signature invalide ou absente)', [
                'has_signature_header' => $signatureHeader !== null,
            ]);

            // Ne jamais faire confiance au corps avant signature valide : 401, pas de traitement.
            return new JsonResponse(['error' => 'invalid_signature'], 401);
        }

        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'invalid_payload'], 400);
        }

        $merchantReference = $data['merchantPaymentReference'] ?? null;
        $paymentStatus = $data['paymentStatus'] ?? null;
        $papiOwnReference = $data['paymentReference'] ?? null;
        $paymentMethod = $data['paymentMethod'] ?? null;

        $this->logger->info('Papi: notification de paiement reçue et vérifiée', [
            'merchant_payment_reference' => $merchantReference,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'amount' => $data['amount'] ?? null,
        ]);

        if (!is_string($merchantReference) || $merchantReference === '') {
            return new JsonResponse(['ok' => true], 200);
        }

        $order = $importOrderRepository->findOneBy(['orderNumber' => $merchantReference]);
        if ($order instanceof ImportOrder) {
            return $this->handleImportOrderNotification(
                $order,
                $paymentStatus,
                $papiOwnReference,
                $merchantReference,
                $paymentMethod,
                $importOrderTrackerService
            );
        }

        $quote = $quoteRepository->findOneBy(['quoteNumber' => $merchantReference]);
        if ($quote instanceof Quote) {
            return $this->handleQuoteNotification(
                $quote,
                $paymentStatus,
                $papiOwnReference,
                $merchantReference,
                $paymentMethod,
                $entityManager
            );
        }

        // Référence inconnue des deux côtés : rien à traiter, mais on acquitte quand même
        // pour éviter que Papi ne retente indéfiniment une notification orpheline.
        $this->logger->warning('Papi: notification pour une commande/devis introuvable', [
            'merchant_payment_reference' => $merchantReference,
        ]);

        return new JsonResponse(['ok' => true], 200);
    }

    private function handleImportOrderNotification(
        ImportOrder $order,
        ?string $paymentStatus,
        mixed $papiOwnReference,
        string $merchantReference,
        ?string $paymentMethod,
        ImportOrderTrackerService $importOrderTrackerService
    ): JsonResponse {
        if ($paymentStatus !== 'SUCCESS') {
            // PENDING ou FAILED : on journalise, on ne change pas le statut. Le client peut
            // relancer le paiement depuis la page de confirmation ("Payer maintenant").
            return new JsonResponse(['ok' => true], 200);
        }

        if ($order->getStatus() === 'paid') {
            // Notification dupliquée (Papi peut réessayer) : idempotent, on ne refait rien.
            $this->logger->info('Papi: notification de succès ignorée, commande déjà marquée payée', [
                'order_number' => $order->getOrderNumber(),
            ]);

            return new JsonResponse(['ok' => true], 200);
        }

        if ($order->getStatus() !== 'registered') {
            // Statut déjà avancé autrement (ex. annulée) : on ne force pas la transition.
            $this->logger->warning('Papi: paiement confirmé mais la commande n\'est plus au statut "registered"', [
                'order_number' => $order->getOrderNumber(),
                'current_status' => $order->getStatus(),
            ]);

            return new JsonResponse(['ok' => true], 200);
        }

        $order->setPaymentReference(is_string($papiOwnReference) ? $papiOwnReference : $merchantReference);

        $importOrderTrackerService->changeStatus(
            $order,
            'paid',
            sprintf('Paiement confirmé automatiquement via Papi (%s).', $paymentMethod ?? 'mobile money'),
            'papi_webhook'
        );

        $this->logger->info('Papi: commande marquée payée', [
            'order_number' => $order->getOrderNumber(),
        ]);

        return new JsonResponse(['ok' => true], 200);
    }

    private function handleQuoteNotification(
        Quote $quote,
        ?string $paymentStatus,
        mixed $papiOwnReference,
        string $merchantReference,
        ?string $paymentMethod,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if ($paymentStatus !== 'SUCCESS') {
            // PENDING ou FAILED : on journalise, on ne change pas le statut. Le client peut
            // relancer le paiement depuis la page de suivi ("Payer maintenant").
            return new JsonResponse(['ok' => true], 200);
        }

        if ($quote->getPaymentStatus() === 'completed') {
            // Notification dupliquée (Papi peut réessayer) : idempotent, on ne refait rien.
            $this->logger->info('Papi: notification de succès ignorée, devis déjà marqué payé', [
                'quote_number' => $quote->getQuoteNumber(),
            ]);

            return new JsonResponse(['ok' => true], 200);
        }

        if ($quote->getPaymentStatus() !== 'pending') {
            // Statut déjà autre chose (ex. not_required) : on ne force pas la transition.
            $this->logger->warning('Papi: paiement confirmé mais le devis n\'est plus au statut de paiement "pending"', [
                'quote_number' => $quote->getQuoteNumber(),
                'current_payment_status' => $quote->getPaymentStatus(),
            ]);

            return new JsonResponse(['ok' => true], 200);
        }

        $previousStatus = (string) $quote->getStatus();
        $reference = is_string($papiOwnReference) ? $papiOwnReference : $merchantReference;

        $quote->setPaymentStatus('completed');
        $quote->setTransactionReference($reference);

        $history = new QuoteStatusHistory();
        $history->setQuote($quote);
        $history->setOldStatus($previousStatus);
        $history->setNewStatus($previousStatus);
        $history->setChangedBy('papi_webhook');
        $history->setComment(sprintf(
            'Paiement des frais de devis confirmé automatiquement via Papi (%s), référence %s.',
            $paymentMethod ?? 'mobile money',
            $reference
        ));
        $entityManager->persist($history);
        $entityManager->flush();

        $this->logger->info('Papi: devis marqué payé', [
            'quote_number' => $quote->getQuoteNumber(),
        ]);

        return new JsonResponse(['ok' => true], 200);
    }
}
