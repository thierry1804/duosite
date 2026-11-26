<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Psr\Log\LoggerInterface;

class ExchangeRateService
{
    private $httpClient;
    private $logger;
    private $cache;
    private const CACHE_KEY_INFO = 'rmb_mga_exchange_info';
    private const API_URL = 'https://open.er-api.com/v6/latest/CNY';

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * Récupère le taux de change RMB vers MGA du jour.
     *
     * Dérivé de getExchangeRateInfo() pour garantir que ce taux et les
     * métadonnées renvoyées par getExchangeRateInfo() ne divergent jamais
     * (même cache, même appel API : une seule source de vérité).
     *
     * @return float|null Le taux de change, ou null si indisponible (API en panne et pas de cache exploitable)
     */
    public function getRmbMgaRate(): ?float
    {
        $info = $this->getExchangeRateInfo();

        return $info['rate'] !== null ? (float) $info['rate'] : null;
    }

    /**
     * Récupère toutes les informations du taux de change RMB/MGA (taux + métadonnées).
     *
     * Le résultat est mis en cache jusqu'à la prochaine mise à jour annoncée par l'API
     * (ou 24h par défaut). En cas d'échec de l'API, on ne renvoie JAMAIS une valeur
     * inventée : on sert le dernier taux connu s'il est encore en cache (même périmé),
     * sinon `rate => null` avec `provider => 'unavailable'`, afin que les appelants
     * puissent distinguer "pas de taux" d'un taux réel.
     *
     * @return array{rate: float|null, last_update: string|null, next_update: string|null, provider: string}
     */
    public function getExchangeRateInfo(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_INFO);

        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $now = new \DateTime();

            if (isset($data['next_update']) && $data['next_update'] !== null) {
                $nextUpdate = new \DateTime($data['next_update']);

                if ($nextUpdate > $now) {
                    $this->logger->info('Exchange rate retrieved from cache', [
                        'rate' => $data['rate'],
                        'next_update' => $data['next_update'],
                    ]);

                    return $data;
                }
            }
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL);
            $content = $response->toArray();

            if (!isset($content['rates']['MGA'])) {
                throw new \RuntimeException('Taux MGA non disponible dans la réponse API');
            }

            $rate = (float) $content['rates']['MGA'];
            $lastUpdateTime = isset($content['time_last_update_unix'])
                ? (new \DateTime())->setTimestamp($content['time_last_update_unix'])
                : null;
            $nextUpdateTime = isset($content['time_next_update_unix'])
                ? (new \DateTime())->setTimestamp($content['time_next_update_unix'])
                : null;

            $data = [
                'rate' => $rate,
                'last_update' => $lastUpdateTime?->format('Y-m-d H:i:s'),
                'next_update' => $nextUpdateTime?->format('Y-m-d H:i:s'),
                'provider' => 'open.er-api.com',
            ];

            $cacheItem->set($data);
            // Le TTL réel du cache (PSR-6) doit survivre à next_update : next_update ne sert
            // qu'à décider si on doit rappeler l'API (logique ci-dessus), pas à faire disparaître
            // l'entrée. Sinon l'entrée périmée n'est plus là pour servir de secours si l'API tombe.
            $cacheItem->expiresAfter(7 * 86400);
            $this->cache->save($cacheItem);

            $this->logger->info('Exchange rate retrieved from API', [
                'rate' => $rate,
                'next_update' => $data['next_update'],
            ]);

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la récupération du taux de change RMB/MGA: ' . $e->getMessage());

            // Dégradation : on préfère servir le dernier taux connu (même périmé)
            // plutôt qu'une valeur inventée, pour ne pas fausser silencieusement
            // les devis. On ne renvoie 'rate' => null que si on n'a vraiment rien.
            if ($cacheItem->isHit()) {
                $stale = $cacheItem->get();
                $this->logger->warning('Utilisation du taux de change RMB/MGA périmé suite à une erreur API', [
                    'rate' => $stale['rate'] ?? null,
                    'last_update' => $stale['last_update'] ?? null,
                ]);

                return $stale;
            }

            return [
                'rate' => null,
                'last_update' => null,
                'next_update' => null,
                'provider' => 'unavailable',
            ];
        }
    }
}
