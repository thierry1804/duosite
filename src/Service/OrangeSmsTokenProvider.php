<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fournit un token OAuth 2.0 client_credentials pour l'API Orange SMS (Africa & Middle East).
 * Le token est mis en cache (TTL 3500 s) pour éviter des appels répétés.
 *
 * @see https://developer.orange.com/apis/sms/getting-started
 */
class OrangeSmsTokenProvider
{
    private const TOKEN_URL = 'https://api.orange.com/oauth/v3/token';
    private const CACHE_KEY = 'orange_sms_access_token';
    private const CACHE_TTL = 3500;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private string $clientId,
        private string $clientSecret
    ) {
    }

    /**
     * Retourne un token d'accès valide (depuis le cache ou en le demandant à l'API).
     *
     * @throws \RuntimeException si l'API Orange renvoie une erreur
     */
    public function getAccessToken(): string
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_TTL);

            $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'body' => 'grant_type=client_credentials',
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $this->logger->error('Orange SMS token error', [
                    'status' => $statusCode,
                    'response' => $data,
                ]);
                throw new \RuntimeException(sprintf(
                    'Orange SMS: échec d\'authentification (HTTP %d). %s',
                    $statusCode,
                    $data['message'] ?? $data['description'] ?? json_encode($data)
                ));
            }

            if (empty($data['access_token'])) {
                $this->logger->error('Orange SMS token response missing access_token', ['response' => $data]);
                throw new \RuntimeException('Orange SMS: réponse token invalide (access_token manquant).');
            }

            return (string) $data['access_token'];
        });
    }

    /**
     * Invalide le token en cache (utile après un 401 pour forcer un nouveau token).
     */
    public function clearToken(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }
}
