<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Repository\QuoteRepository;
use App\Service\QuoteTrackerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrackingController extends AbstractController
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private QuoteTrackerService $quoteTrackerService
    ) {}

    /**
     * Page publique de suivi d'un devis via token de tracking
     */
    #[Route('/tracking/{token}', name: 'app_tracking_show', methods: ['GET'])]
    public function show(string $token): Response
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            throw $this->createNotFoundException('Token de suivi invalide ou devis introuvable.');
        }

        // Récupérer l'historique des changements de statut
        $statusHistory = $this->quoteTrackerService->getStatusHistory($quote);
        
        // Récupérer les offres associées au devis
        $offers = $quote->getOffers();

        return $this->render('tracking/show.html.twig', [
            'quote' => $quote,
            'statusHistory' => $statusHistory,
            'offers' => $offers,
            'trackingToken' => $token,
            'quoteTrackerService' => $this->quoteTrackerService
        ]);
    }

    /**
     * API JSON pour récupérer les informations de suivi d'un devis
     */
    #[Route('/api/tracking/{token}', name: 'app_tracking_api', methods: ['GET'])]
    public function api(string $token): JsonResponse
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            return new JsonResponse([
                'error' => 'Token de suivi invalide ou devis introuvable.',
                'code' => 'QUOTE_NOT_FOUND'
            ], 404);
        }

        // Récupérer l'historique des changements de statut
        $statusHistory = $this->quoteTrackerService->getStatusHistory($quote);
        
        // Formater l'historique pour l'API
        $formattedHistory = [];
        foreach ($statusHistory as $history) {
            $formattedHistory[] = [
                'id' => $history->getId(),
                'old_status' => $history->getOldStatus(),
                'new_status' => $history->getNewStatus(),
                'old_status_label' => $history->getOldStatusLabel(),
                'new_status_label' => $history->getNewStatusLabel(),
                'changed_by' => $history->getChangedBy(),
                'comment' => $history->getComment(),
                'created_at' => $history->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ];
        }

        // Informations du devis (sans données sensibles)
        $quoteData = [
            'id' => $quote->getId(),
            'quote_number' => $quote->getQuoteNumber(),
            'status' => $quote->getStatus(),
            'status_label' => $this->quoteTrackerService->getStatusLabel($quote->getStatus()),
            'created_at' => $quote->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $quote->getUpdatedAt() ? $quote->getUpdatedAt()->format(\DateTimeInterface::ATOM) : null,
            'customer_name' => $quote->getFullName(),
            'has_offers' => $quote->getOffers()->count() > 0,
            'offers_count' => $quote->getOffers()->count()
        ];

        return new JsonResponse([
            'success' => true,
            'quote' => $quoteData,
            'status_history' => $formattedHistory,
            'tracking_token' => $token,
            'last_updated' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ]);
    }

    /**
     * API pour récupérer uniquement le statut actuel d'un devis
     */
    #[Route('/api/tracking/{token}/status', name: 'app_tracking_status_api', methods: ['GET'])]
    public function statusApi(string $token): JsonResponse
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            return new JsonResponse([
                'error' => 'Token de suivi invalide ou devis introuvable.',
                'code' => 'QUOTE_NOT_FOUND'
            ], 404);
        }

        $lastChange = $this->quoteTrackerService->getLastStatusChange($quote);

        return new JsonResponse([
            'success' => true,
            'quote_number' => $quote->getQuoteNumber(),
            'status' => $quote->getStatus(),
            'status_label' => $this->quoteTrackerService->getStatusLabel($quote->getStatus()),
            'last_change' => $lastChange ? [
                'changed_by' => $lastChange->getChangedBy(),
                'comment' => $lastChange->getComment(),
                'created_at' => $lastChange->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ] : null,
            'updated_at' => $quote->getUpdatedAt() ? $quote->getUpdatedAt()->format(\DateTimeInterface::ATOM) : null
        ]);
    }

    /**
     * Page de recherche de devis par numéro (pour les clients qui ont perdu leur token)
     */
    #[Route('/tracking/search', name: 'app_tracking_search', methods: ['GET', 'POST'])]
    public function search(Request $request): Response
    {
        $quote = null;
        $error = null;
        $searchPerformed = false;

        if ($request->isMethod('POST')) {
            $searchPerformed = true;
            $quoteNumber = $request->request->get('quote_number');
            $email = $request->request->get('email');

            if (empty($quoteNumber) || empty($email)) {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $quote = $this->quoteRepository->findOneBy([
                    'quoteNumber' => $quoteNumber,
                    'email' => $email
                ]);

                if (!$quote) {
                    $error = 'Aucun devis trouvé avec ces informations.';
                }
            }
        }

        return $this->render('tracking/search.html.twig', [
            'quote' => $quote,
            'error' => $error,
            'searchPerformed' => $searchPerformed
        ]);
    }

    /**
     * Webhook pour les systèmes tiers (avec authentification HMAC)
     */
    #[Route('/webhook/quote-status', name: 'app_tracking_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        // Vérifier la signature HMAC (à implémenter selon vos besoins)
        $signature = $request->headers->get('X-Webhook-Signature');
        $payload = $request->getContent();
        
        // TODO: Implémenter la vérification HMAC
        // $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        // if (!hash_equals($expectedSignature, $signature)) {
        //     return new JsonResponse(['error' => 'Invalid signature'], 401);
        // }

        $data = json_decode($payload, true);
        
        if (!$data || !isset($data['quote_id']) || !isset($data['status'])) {
            return new JsonResponse([
                'error' => 'Invalid payload format',
                'required_fields' => ['quote_id', 'status']
            ], 400);
        }

        $quote = $this->quoteRepository->find($data['quote_id']);
        
        if (!$quote) {
            return new JsonResponse([
                'error' => 'Quote not found',
                'quote_id' => $data['quote_id']
            ], 404);
        }

        try {
            $this->quoteTrackerService->changeStatus(
                $quote,
                $data['status'],
                $data['comment'] ?? null,
                $data['changed_by'] ?? 'webhook'
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Quote status updated successfully',
                'quote_id' => $quote->getId(),
                'new_status' => $quote->getStatus()
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Invalid status transition',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * API pour récupérer les statistiques de tracking (admin uniquement)
     */
    #[Route('/api/tracking/stats', name: 'app_tracking_stats_api', methods: ['GET'])]
    public function statsApi(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if (!$startDate || !$endDate) {
            return new JsonResponse([
                'error' => 'start_date and end_date parameters are required',
                'format' => 'Y-m-d'
            ], 400);
        }

        try {
            $start = new \DateTimeImmutable($startDate);
            $end = new \DateTimeImmutable($endDate);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid date format',
                'format' => 'Y-m-d'
            ], 400);
        }

        $stats = $this->quoteTrackerService->getStatusChangeStats($start, $end);

        return new JsonResponse([
            'success' => true,
            'period' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d')
            ],
            'statistics' => $stats
        ]);
    }

    /**
     * Page de réponse à l'offre via token de tracking
     */
    #[Route('/tracking/{token}/respond', name: 'app_tracking_respond', methods: ['GET'])]
    public function respond(string $token): Response
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            throw $this->createNotFoundException('Token de suivi invalide ou devis introuvable.');
        }

        if ($quote->getStatus() !== 'waiting_customer') {
            $this->addFlash('warning', 'Cette offre n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_tracking_show', ['token' => $token]);
        }

        // Récupérer la dernière offre
        $offer = $quote->getOffers()->last();
        if (!$offer) {
            throw $this->createNotFoundException('Aucune offre disponible pour ce devis.');
        }

        return $this->render('tracking/respond.html.twig', [
            'quote' => $quote,
            'offer' => $offer,
            'trackingToken' => $token
        ]);
    }

    /**
     * Accepter l'offre via token de tracking
     */
    #[Route('/tracking/{token}/accept', name: 'app_tracking_accept', methods: ['POST'])]
    public function acceptOffer(string $token, Request $request): Response
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            throw $this->createNotFoundException('Token de suivi invalide ou devis introuvable.');
        }

        if ($quote->getStatus() !== 'waiting_customer') {
            $this->addFlash('error', 'Cette offre n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_tracking_show', ['token' => $token]);
        }

        // Récupérer la dernière offre
        $offer = $quote->getOffers()->last();
        if (!$offer) {
            throw $this->createNotFoundException('Aucune offre disponible pour ce devis.');
        }

        try {
            // Mettre à jour le statut de l'offre
            $offer->setStatus('accepted');
            
            // Utiliser le service de tracking pour changer le statut du devis
            $this->quoteTrackerService->changeStatus(
                $quote, 
                'accepted', 
                'Offre acceptée par le client via le système de suivi public', 
                'client_tracking'
            );
            
            $this->addFlash('success', 'Vous avez accepté l\'offre avec succès. Nous vous contacterons bientôt pour finaliser votre commande.');
            
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Erreur lors de l\'acceptation de l\'offre : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_tracking_show', ['token' => $token]);
    }

    /**
     * Refuser l'offre via token de tracking
     */
    #[Route('/tracking/{token}/decline', name: 'app_tracking_decline', methods: ['POST'])]
    public function declineOffer(string $token, Request $request): Response
    {
        $quote = $this->quoteRepository->findOneBy(['trackingToken' => $token]);
        
        if (!$quote) {
            throw $this->createNotFoundException('Token de suivi invalide ou devis introuvable.');
        }

        if ($quote->getStatus() !== 'waiting_customer') {
            $this->addFlash('error', 'Cette offre n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_tracking_show', ['token' => $token]);
        }

        // Récupérer la dernière offre
        $offer = $quote->getOffers()->last();
        if (!$offer) {
            throw $this->createNotFoundException('Aucune offre disponible pour ce devis.');
        }

        try {
            // Mettre à jour le statut de l'offre
            $offer->setStatus('declined');
            
            // Utiliser le service de tracking pour changer le statut du devis
            $this->quoteTrackerService->changeStatus(
                $quote, 
                'declined', 
                'Offre refusée par le client via le système de suivi public', 
                'client_tracking'
            );
            
            // Remettre en cours de traitement pour nouvelle offre
            $this->quoteTrackerService->changeStatus(
                $quote, 
                'in_progress', 
                'Devis remis en cours de traitement pour nouvelle offre', 
                'client_tracking'
            );
            
            $this->addFlash('info', 'Vous avez refusé l\'offre. Notre équipe va vous proposer une nouvelle offre.');
            
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', 'Erreur lors du refus de l\'offre : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_tracking_show', ['token' => $token]);
    }
}
