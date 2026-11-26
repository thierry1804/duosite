<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Passerelle de paiement mobile money Papi (MVola, Orange Money, Airtel Money) pour Madagascar.
 *
 * @see https://docs.papi.mg/docs/quickstart
 * @see https://docs.papi.mg/docs/developper-guide/securing-notifications
 */
class PapiPaymentService
{
    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey,
        private string $webhookSecret,
        private string $apiBaseUrl = 'https://app.papi.mg/engine/api',
    ) {
    }

    /**
     * Crée un lien de paiement à envoyer/rediriger vers le client.
     *
     * @param string|null $provider Restreint le lien à un prestataire : MVOLA, AIRTEL_MONEY, ORANGE_MONEY, BRED.
     *                               Laisser null pour que le client choisisse sur la page de paiement.
     *
     * @return array{paymentLink: string, shortLink: string, notificationToken: string, isTestMode: bool}
     *
     * @throws \RuntimeException si Papi renvoie une erreur
     */
    public function createPaymentLink(
        float $amount,
        string $clientName,
        string $reference,
        string $description,
        ?string $notificationUrl = null,
        ?string $successUrl = null,
        ?string $failureUrl = null,
        ?string $provider = null,
    ): array {
        $body = array_filter([
            'amount' => $amount,
            'clientName' => $clientName,
            'reference' => $reference,
            'description' => $description,
            'notificationUrl' => $notificationUrl,
            'successUrl' => $successUrl,
            'failureUrl' => $failureUrl,
            'provider' => $provider,
        ], static fn ($v) => $v !== null);

        try {
            $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/payment-links', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Token' => $this->apiKey,
                ],
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $this->logger->error('Papi: échec de création du lien de paiement', [
                    'status' => $statusCode,
                    'reference' => $reference,
                    'response' => $data,
                ]);
                throw new \RuntimeException(sprintf('Papi: création du lien de paiement échouée (HTTP %d).', $statusCode));
            }

            $this->logger->info('Papi: lien de paiement créé', [
                'reference' => $reference,
                'is_test_mode' => $data['data']['isTestMode'] ?? null,
            ]);

            return $data['data'];
        } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface $e) {
            $this->logger->error('Papi: erreur réseau lors de la création du lien de paiement', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Papi: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Relit l'état d'un lien de paiement via votre référence marchand.
     * À utiliser quand une notification n'est jamais arrivée (serveur down, etc.).
     *
     * @return array{linkStatus: string, paymentStatus: ?string}
     */
    public function getPaymentLinkStatus(string $merchantPaymentReference): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiBaseUrl . '/payment-links/' . rawurlencode($merchantPaymentReference),
                [
                    'headers' => [
                        'Token' => $this->apiKey,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $this->logger->error('Papi: échec de lecture du statut du lien de paiement', [
                    'status' => $statusCode,
                    'reference' => $merchantPaymentReference,
                    'response' => $data,
                ]);
                throw new \RuntimeException(sprintf('Papi: lecture du statut échouée (HTTP %d).', $statusCode));
            }

            return $data['data'];
        } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface $e) {
            $this->logger->error('Papi: erreur réseau lors de la lecture du statut du lien de paiement', [
                'reference' => $merchantPaymentReference,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Papi: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Vérifie l'en-tête X-Papi-Signature d'une notification (callback) Papi.
     *
     * Ne fait confiance au corps de la requête QUE si cette méthode renvoie true.
     * Voir https://docs.papi.mg/docs/developper-guide/securing-notifications
     *
     * @param string $rawBody Corps brut de la requête (octets exacts reçus, non reparsé)
     * @param string|null $signatureHeader Valeur de l'en-tête X-Papi-Signature, ex. "t=1757750400,v1=66c4..."
     */
    public function verifyNotificationSignature(
        string $rawBody,
        ?string $signatureHeader,
        int $toleranceSeconds = self::SIGNATURE_TOLERANCE_SECONDS,
    ): bool {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $item) {
            $pair = explode('=', trim($item), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if ($timestamp === null || $signature === null) {
            return false;
        }
        if (!preg_match('/^\d+$/', $timestamp) || !preg_match('/^[0-9a-f]{64}$/', $signature)) {
            return false;
        }

        if ($toleranceSeconds > 0 && abs(time() - (int) $timestamp) > $toleranceSeconds) {
            $this->logger->warning('Papi: notification rejetée, timestamp hors tolérance (rejeu possible)', [
                'timestamp' => $timestamp,
            ]);

            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }
}
