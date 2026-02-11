<?php

namespace App\Controller;

use App\Service\PendingQuoteAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Route publique (sans authentification) pour déclencher l'alerte des devis en attente.
 * Protégée par un token secret pour être appelée par un cron (curl ou tâche planifiée).
 *
 * Usage : GET https://duoimport.mg/quote/notifier?token=VOTRE_SECRET
 * Cron : curl -s "https://duoimport.mg/quote/notifier?token=VOTRE_SECRET"
 */
class QuoteNotifierController extends AbstractController
{
    public function __construct(
        private PendingQuoteAlertService $pendingQuoteAlertService,
        private string $notifierSecret
    ) {
    }

    #[Route('/quote/notifier', name: 'app_quote_notifier', methods: ['GET'])]
    public function notifier(Request $request): Response
    {
        $token = $request->query->get('token', '');
        if ($this->notifierSecret === '' || $token !== $this->notifierSecret) {
            return new JsonResponse(['error' => 'Forbidden', 'message' => 'Token invalide ou manquant.'], Response::HTTP_FORBIDDEN);
        }

        $days = (int) $request->query->get('days', 2);
        $force = $request->query->get('force', '') === '1' || $request->query->get('force', '') === 'true';

        $result = $this->pendingQuoteAlertService->run($days, $force);

        return new JsonResponse([
            'ok' => $result['ok'],
            'count' => $result['count'],
            'days' => $result['days'],
            'message' => $result['message'],
            'sms_sent' => $result['sms_sent'],
            'sms_failures' => $result['sms_failures'],
            'email_sent' => $result['email_sent'],
            'email_error' => $result['email_error'],
        ], Response::HTTP_OK);
    }
}
