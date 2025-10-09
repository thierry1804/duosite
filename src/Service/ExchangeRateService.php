<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class ExchangeRateService
{
    private $httpClient;
    private $logger;
    private $cache;
    private const CACHE_KEY_RATE = 'rmb_mga_exchange_rate';
    private const CACHE_KEY_INFO = 'rmb_mga_exchange_info';
    private const API_URL = 'https://open.er-api.com/v6/latest/CNY';

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * Récupère le taux de change RMB vers MGA du jour
     * 
     * @return float|null Le taux de change ou null en cas d'erreur
     */
    public function getRmbMgaRate()
    {
        try {
            // Utiliser le cache pour stocker le taux pendant 24h ou jusqu'à la prochaine mise à jour
            return $this->cache->get(self::CACHE_KEY_RATE, function (ItemInterface $item) {
                $response = $this->httpClient->request('GET', self::API_URL);
                $data = $response->toArray();
                
                if (isset($data['rates']['MGA'])) {
                    // Définir l'expiration du cache en fonction de la prochaine mise à jour de l'API
                    if (isset($data['time_next_update_unix'])) {
                        $nextUpdate = $data['time_next_update_unix'];
                        $now = time();
                        $cacheLifetime = $nextUpdate - $now;
                        
                        // Si la prochaine mise à jour est valide, utiliser cette valeur pour la durée du cache
                        if ($cacheLifetime > 0) {
                            $item->expiresAfter($cacheLifetime);
                        } else {
                            // Sinon, conserver par défaut pendant 24h
                            $item->expiresAfter(86400); // 24 heures en secondes
                        }
                    } else {
                        // Si l'information de prochaine mise à jour n'est pas disponible, conserver 24h
                        $item->expiresAfter(86400);
                    }
                    
                    // Retourner le taux de change MGA
                    return (float)$data['rates']['MGA'];
                }
                
                // En cas d'erreur, conserver le cache pour 1h seulement
                $item->expiresAfter(3600);
                throw new \Exception('Taux MGA non disponible dans la réponse API');
            });
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du taux de change RMB/MGA: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère toutes les informations du taux de change RMB/MGA
     * 
     * @return array Informations complètes sur le taux de change
     */
    public function getExchangeRateInfo(): array
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY_INFO);
        
        // Vérifier si les données sont en cache et toujours valides
        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            $now = new \DateTime();
            
            // Vérifier si next_update existe avant de l'utiliser
            if (isset($data['next_update']) && $data['next_update'] !== null) {
                $nextUpdate = new \DateTime($data['next_update']);
                
                // Utiliser les données en cache si la date de prochaine mise à jour n'est pas encore passée
                if ($nextUpdate > $now) {
                    $this->logger->info('Exchange rate retrieved from cache', [
                        'rate' => $data['rate'],
                        'next_update' => $data['next_update']
                    ]);
                    return $data;
                }
            }
        }
        
        // Si pas de cache valide, faire un appel API
        try {
            $response = $this->httpClient->request('GET', self::API_URL);
            $content = $response->toArray();
            
            if (isset($content['rates']['MGA'])) {
                $rate = $content['rates']['MGA'];
                $lastUpdateTime = null;
                $nextUpdateTime = null;
                
                // Extraire les dates de mise à jour
                if (isset($content['time_last_update_unix'])) {
                    $lastUpdateTime = (new \DateTime())->setTimestamp($content['time_last_update_unix']);
                }
                
                if (isset($content['time_next_update_unix'])) {
                    $nextUpdateTime = (new \DateTime())->setTimestamp($content['time_next_update_unix']);
                }
                
                // Formater les informations à stocker
                $data = [
                    'rate' => $rate,
                    'last_update' => $lastUpdateTime ? $lastUpdateTime->format('Y-m-d H:i:s') : null,
                    'next_update' => $nextUpdateTime ? $nextUpdateTime->format('Y-m-d H:i:s') : null,
                    'provider' => 'open.er-api.com'
                ];
                
                // Définir la durée du cache jusqu'à la prochaine mise à jour
                if ($nextUpdateTime) {
                    $cacheExpiration = $nextUpdateTime;
                } else {
                    // Par défaut, cache pour 24 heures si pas d'information de prochaine mise à jour
                    $cacheExpiration = (new \DateTime())->add(new \DateInterval('PT24H'));
                }
                
                $cacheItem->set($data);
                $cacheItem->expiresAt($cacheExpiration);
                $this->cache->save($cacheItem);
                
                // Mettre également à jour le cache pour le taux simple
                $rateItem = $this->cache->getItem(self::CACHE_KEY_RATE);
                $rateItem->set((float)$rate);
                if ($nextUpdateTime) {
                    $rateItem->expiresAt($nextUpdateTime);
                } else {
                    $rateItem->expiresAfter(86400);
                }
                $this->cache->save($rateItem);
                
                $this->logger->info('Exchange rate retrieved from API', [
                    'rate' => $rate,
                    'next_update' => $nextUpdateTime ? $nextUpdateTime->format('Y-m-d H:i:s') : null
                ]);
                
                return $data;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error fetching exchange rate', ['error' => $e->getMessage()]);
        }
        
        // Valeur par défaut en cas d'échec
        return [
            'rate' => 600.0,
            'last_update' => null,
            'next_update' => null,
            'provider' => 'default'
        ];
    }
} 