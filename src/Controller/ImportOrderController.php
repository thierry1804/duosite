<?php

namespace App\Controller;

use App\Entity\ImportOrder;
use App\Entity\ImportOrderItem;
use App\Form\ImportOrderType;
use App\Repository\ImportOrderRepository;
use App\Repository\ImportProductRepository;
use App\Service\PapiPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImportOrderController extends AbstractController
{
    /** Méthodes de paiement réglées en ligne via Papi (mobile money). */
    private const PAPI_PROVIDERS = [
        'mvola' => 'MVOLA',
        'orange_money' => 'ORANGE_MONEY',
        'airtel_money' => 'AIRTEL_MONEY',
    ];

    #[Route('/api/import-product/{code}', name: 'api_import_product_by_code', methods: ['GET'])]
    public function apiProductByCode(Request $request, string $code, ImportProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->findOneByCode($code);
        if (!$product) {
            return new JsonResponse(['error' => 'Code produit invalide'], 404);
        }
        $photoUrl = $product->getPhotoFilename()
            ? '/uploads/import_products/' . $product->getPhotoFilename()
            : null;
        return new JsonResponse([
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'availableColors' => $product->getAvailableColors(),
            'photoUrl' => $photoUrl,
        ]);
    }

    #[Route('/commande-import', name: 'app_import_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        #[Autowire('%app.quote_and_order_suspended%')]
        bool $quoteAndOrderSuspended,
        EntityManagerInterface $entityManager,
        ImportProductRepository $productRepository,
        MailerInterface $mailer,
        PapiPaymentService $papiPaymentService,
        LoggerInterface $logger,
    ): Response {
        $order = new ImportOrder();
        if ($order->getItems()->count() === 0) {
            $order->addItem(new ImportOrderItem());
        }

        $hashPayload = null;
        $hParam = trim($request->query->get('h', ''));
        if ($hParam !== '') {
            try {
                $decoded = base64_decode($hParam, true);
                if ($decoded !== false) {
                    // External service sends base64(urlencode(json)).
                    // Keep a fallback on raw decoded JSON for backward compatibility.
                    $urlDecoded = urldecode($decoded);
                    $data = json_decode($urlDecoded, true);
                    if (!is_array($data)) {
                        $data = json_decode($decoded, true);
                    }
                    if (is_array($data)) {
                        $hashPayload = $data;
                        $firstItem = $order->getItems()->first();
                        if ($firstItem) {
                            $firstItem->setProductCode($this->resolveHashProductCode($data));
                            if (!empty($data['color'])) {
                                $firstItem->setColor((string) $data['color']);
                            }
                            if (!empty($data['qty']) && is_numeric($data['qty'])) {
                                $firstItem->setQuantity((int) $data['qty']);
                            }
                            if (!empty($data['name'])) {
                                $firstItem->setProductName((string) $data['name']);
                            }
                            $hashPrice = $this->normalizeHashPrice($data['price'] ?? null);
                            if ($hashPrice !== null) {
                                $firstItem->setProductPrice($hashPrice);
                            }
                            if (!empty($data['img']) && is_string($data['img'])) {
                                $firstItem->setProductPhotoFilename((string) $data['img']);
                            }
                        }
                    }
                }
            } catch (\Throwable) {
                $hashPayload = null;
            }
        }

        $form = $this->createForm(ImportOrderType::class, $order);
        if ($request->isMethod('POST') && $quoteAndOrderSuspended) {
            $this->addFlash(
                'warning',
                'La passation de commande en ligne est momentanément indisponible. Merci de contacter le service commercial.'
            );

            return $this->redirectToRoute('app_import_order_new');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $items = $order->getItems();
            if ($items->isEmpty()) {
                $this->addFlash('error', 'Ajoutez au moins un produit.');
                return $this->render('import_order/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $hasError = false;
            foreach ($items as $item) {
                $code = $item->getProductCode();
                if ($code === null || $code === '') {
                    $this->addFlash('error', 'Chaque ligne doit avoir un code produit.');
                    $hasError = true;
                    break;
                }
                $normalizedCode = trim($code);
                if ($this->isExternalHashCode($normalizedCode)) {
                    $name = trim((string) $item->getProductName());
                    $price = trim((string) $item->getProductPrice());
                    if ($name === '' || $price === '') {
                        $this->addFlash('error', 'Produit externe invalide: nom ou prix manquant.');
                        $hasError = true;
                        break;
                    }
                    $item->setProductCode($normalizedCode);
                } else {
                    $product = $productRepository->findOneByCode($normalizedCode);
                    if (!$product) {
                        $this->addFlash('error', sprintf('Code produit invalide : %s.', $code));
                        $hasError = true;
                        break;
                    }
                    $item->setProductName($product->getName());
                    $item->setProductPrice($product->getPrice());
                    $item->setProductPhotoFilename($product->getPhotoFilename());
                    $item->setProductCode($normalizedCode);
                }
                $color = $item->getColor();
                if ($color === null || trim($color) === '') {
                    $this->addFlash('error', 'Chaque produit doit avoir une couleur.');
                    $hasError = true;
                    break;
                }
                $item->setColor(trim($color));
            }
            if ($hasError) {
                return $this->render('import_order/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            $entityManager->persist($order);
            $entityManager->flush();

            $trackingUrl = $this->generateUrl('app_import_order_tracking_show', ['token' => $order->getTrackingToken()], true);
            try {
                $email = (new Email())
                    ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
                    ->to($order->getEmail())
                    ->subject('Confirmation de votre commande ' . $order->getOrderNumber())
                    ->html($this->renderView('emails/import_order_confirmation.html.twig', [
                        'order' => $order,
                        'trackingUrl' => $trackingUrl,
                    ]));
                $mailer->send($email);
            } catch (\Throwable $e) {
                // Log but do not block
            }

            // Pour les paiements mobile money (pas en espèces), créer un lien de paiement Papi
            // et rediriger le client vers la page de paiement sécurisée.
            $redirect = $this->createPapiPaymentRedirect($order, $papiPaymentService, $logger);
            if ($redirect !== null) {
                return $redirect;
            }

            return $this->redirectToRoute('app_import_order_confirmation', ['token' => $order->getTrackingToken()]);
        }

        return $this->render('import_order/new.html.twig', [
            'form' => $form->createView(),
            'hashPayload' => $hashPayload,
        ]);
    }

    /**
     * Crée un lien de paiement Papi pour une commande payée en mobile money et renvoie
     * la redirection vers ce lien, ou null si la commande est en espèces ou si la création
     * échoue (dans ce cas un flash warning est ajouté et l'appelant doit continuer normalement).
     */
    private function createPapiPaymentRedirect(
        ImportOrder $order,
        PapiPaymentService $papiPaymentService,
        LoggerInterface $logger
    ): ?RedirectResponse {
        $provider = self::PAPI_PROVIDERS[$order->getPaymentMethod()] ?? null;
        if ($provider === null) {
            return null;
        }

        $confirmationUrl = $this->generateUrl(
            'app_import_order_confirmation',
            ['token' => $order->getTrackingToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $notificationUrl = $this->generateUrl(
            'app_papi_notification',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $paymentLink = $papiPaymentService->createPaymentLink(
                (float) $order->getTotalAmount(),
                $order->getFullName(),
                $order->getOrderNumber(),
                'Commande ' . $order->getOrderNumber() . ' - Duo Import MDG',
                $notificationUrl,
                $confirmationUrl,
                $confirmationUrl,
                $provider
            );

            return new RedirectResponse($paymentLink['paymentLink']);
        } catch (\Throwable $e) {
            $logger->error('Papi: impossible de créer le lien de paiement pour la commande', [
                'order_number' => $order->getOrderNumber(),
                'error' => $e->getMessage(),
            ]);
            $this->addFlash(
                'warning',
                'Le paiement en ligne est momentanément indisponible. Merci de réessayer dans quelques instants ou de nous contacter.'
            );

            return null;
        }
    }

    private function resolveHashProductCode(array $data): string
    {
        if (!empty($data['code']) && is_string($data['code'])) {
            return trim(substr($data['code'], 0, 20));
        }

        $seedParts = [
            (string) ($data['name'] ?? ''),
            (string) ($data['img'] ?? ''),
            (string) ($data['color'] ?? ''),
            (string) ($data['price'] ?? ''),
        ];
        $seed = implode('|', $seedParts);
        $shortHash = strtoupper(substr(hash('sha1', $seed), 0, 12));

        return 'EBV' . $shortHash;
    }

    private function normalizeHashPrice(mixed $rawPrice): ?string
    {
        if ($rawPrice === null || $rawPrice === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9,.\-]/', '', (string) $rawPrice);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function isExternalHashCode(string $code): bool
    {
        return str_starts_with(strtoupper($code), 'EBV');
    }

    #[Route('/commande-import/confirmation/{token}', name: 'app_import_order_confirmation', methods: ['GET'])]
    public function confirmation(string $token, ImportOrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneByTrackingToken($token);
        if (!$order) {
            throw $this->createNotFoundException('Lien de confirmation invalide.');
        }
        return $this->render('import_order/confirmation.html.twig', [
            'order' => $order,
            'isMobileMoneyPending' => $order->getStatus() === 'registered'
                && array_key_exists($order->getPaymentMethod(), self::PAPI_PROVIDERS),
        ]);
    }

    /**
     * Relance un paiement mobile money pour une commande déjà créée mais pas encore payée
     * (ex. après un échec temporaire de la passerelle au moment de la commande).
     */
    #[Route('/commande-import/confirmation/{token}/payer', name: 'app_import_order_pay', methods: ['GET'])]
    public function pay(
        string $token,
        ImportOrderRepository $orderRepository,
        PapiPaymentService $papiPaymentService,
        LoggerInterface $logger
    ): Response {
        $order = $orderRepository->findOneByTrackingToken($token);
        if (!$order) {
            throw $this->createNotFoundException('Lien de confirmation invalide.');
        }
        if ($order->getStatus() !== 'registered' || !array_key_exists($order->getPaymentMethod(), self::PAPI_PROVIDERS)) {
            return $this->redirectToRoute('app_import_order_confirmation', ['token' => $token]);
        }

        $redirect = $this->createPapiPaymentRedirect($order, $papiPaymentService, $logger);

        return $redirect ?? $this->redirectToRoute('app_import_order_confirmation', ['token' => $token]);
    }

    #[Route('/suivi-commande/{token}', name: 'app_import_order_tracking_show', methods: ['GET'])]
    public function trackingShow(string $token, ImportOrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneByTrackingToken($token);
        if (!$order) {
            throw $this->createNotFoundException('Lien de suivi invalide ou commande introuvable.');
        }
        $statusHistory = $order->getStatusHistory();
        return $this->render('import_order/tracking_show.html.twig', [
            'order' => $order,
            'statusHistory' => $statusHistory,
        ]);
    }
}
