<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Envoi de SMS via l'API Orange SMS Africa & Middle East.
 *
 * @see https://developer.orange.com/apis/sms/getting-started
 */
class OrangeSmsSender
{
    private const BASE_URL = 'https://api.orange.com/smsmessaging/v1';

    public function __construct(
        private OrangeSmsTokenProvider $tokenProvider,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $sender
    ) {
    }

    /**
     * Envoie un SMS.
     *
     * @param string $recipient Numéro au format international sans + ni 00 (ex. 261331234567)
     * @param string $message   Texte du SMS
     *
     * @throws \RuntimeException en cas d'erreur HTTP ou API
     */
    public function send(string $recipient, string $message): void
    {
        $attempt = 0;
        $maxAttempts = 2;

        while (true) {
            $attempt++;
            $token = $this->tokenProvider->getAccessToken();
            $url = self::BASE_URL . '/outbound/tel%3A%2B' . $this->sender . '/requests';

            $body = [
                'outboundSMSMessageRequest' => [
                    'address' => 'tel:+' . $recipient,
                    'senderAddress' => 'tel:+' . $this->sender,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ];

            try {
                $response = $this->httpClient->request('POST', $url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $body,
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode === 201) {
                    $this->logger->info('Orange SMS envoyé', [
                        'recipient' => $recipient,
                        'resource_id' => $response->toArray(false)['outboundSMSMessageRequest']['resourceURL'] ?? null,
                    ]);
                    return;
                }

                if ($statusCode === 401 && $attempt < $maxAttempts) {
                    $this->logger->warning('Orange SMS: token expiré ou invalide, nouvel essai avec un nouveau token.');
                    $this->tokenProvider->clearToken();
                    continue;
                }

                $data = $response->toArray(false);
                $this->logger->error('Orange SMS envoi échoué', [
                    'status' => $statusCode,
                    'response' => $data,
                ]);
                throw new \RuntimeException(sprintf(
                    'Orange SMS: envoi échoué (HTTP %d). %s',
                    $statusCode,
                    $data['message'] ?? $data['description'] ?? json_encode($data)
                ));
            } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                if ($statusCode === 401 && $attempt < $maxAttempts) {
                    $this->logger->warning('Orange SMS: token expiré ou invalide, nouvel essai avec un nouveau token.');
                    $this->tokenProvider->clearToken();
                    continue;
                }
                $this->logger->error('Orange SMS envoi erreur client', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Orange SMS: ' . $e->getMessage(), 0, $e);
            } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface $e) {
                $this->logger->error('Orange SMS envoi erreur', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Orange SMS: ' . $e->getMessage(), 0, $e);
            }
        }
    }
}
