<?php

namespace App\Controller;

use App\Entity\ImportOrder;
use App\Repository\ImportOrderRepository;
use App\Service\ImportOrderTrackerService;
use App\Service\PapiPaymentService;
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
        if (!$order instanceof ImportOrder) {
            // Référence inconnue de ce côté : rien à traiter, mais on acquitte quand même
            // pour éviter que Papi ne retente indéfiniment une notification orpheline.
            $this->logger->warning('Papi: notification pour une commande introuvable', [
                'merchant_payment_reference' => $merchantReference,
            ]);

            return new JsonResponse(['ok' => true], 200);
        }

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
}
