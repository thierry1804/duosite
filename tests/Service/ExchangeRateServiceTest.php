<?php

namespace App\Tests\Service;

use App\Service\ExchangeRateService;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateServiceTest extends TestCase
{
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function service(HttpClientInterface $httpClient, ArrayAdapter $cache): ExchangeRateService
    {
        return new ExchangeRateService($httpClient, $this->logger, $cache);
    }

    public function testGetExchangeRateInfoParsesSuccessfulApiResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'rates' => ['MGA' => 650.25],
            'time_last_update_unix' => 1_700_000_000,
            'time_next_update_unix' => 2_000_000_000,
        ])));

        $service = $this->service($httpClient, new ArrayAdapter());
        $info = $service->getExchangeRateInfo();

        $this->assertSame(650.25, $info['rate']);
        $this->assertSame('open.er-api.com', $info['provider']);
        $this->assertNotNull($info['last_update']);
        $this->assertNotNull($info['next_update']);
    }

    public function testGetRmbMgaRateMatchesExchangeRateInfo(): void
    {
        // Les deux méthodes publiques doivent toujours être cohérentes entre elles
        // (régression du bug où getRmbMgaRate() et getExchangeRateInfo() avaient
        // chacune leur propre cache et pouvaient diverger).
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'rates' => ['MGA' => 700.0],
            'time_next_update_unix' => time() + 3600,
        ])));
        $cache = new ArrayAdapter();
        $service = $this->service($httpClient, $cache);

        $info = $service->getExchangeRateInfo();
        $rate = $service->getRmbMgaRate();

        $this->assertSame($info['rate'], $rate);
        $this->assertSame(700.0, $rate);
    }

    public function testReturnsNullRateWithoutInventingAValueWhenApiFailsAndNoCache(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $service = $this->service($httpClient, new ArrayAdapter());

        $info = $service->getExchangeRateInfo();

        $this->assertNull($info['rate']);
        $this->assertSame('unavailable', $info['provider']);
        $this->assertNull($service->getRmbMgaRate());
    }

    public function testServesStaleCacheInsteadOfInventingValueWhenApiFails(): void
    {
        $cache = new ArrayAdapter();

        // Premier appel : succès, met en cache un taux déjà expiré (next_update dans le passé).
        $httpClientOk = new MockHttpClient(new MockResponse(json_encode([
            'rates' => ['MGA' => 610.5],
            'time_next_update_unix' => time() - 10,
        ])));
        $this->service($httpClientOk, $cache)->getExchangeRateInfo();

        // Deuxième appel : l'API est en panne, on doit récupérer le taux périmé, pas 600 par défaut.
        $httpClientDown = new MockHttpClient(new MockResponse('', ['http_code' => 503]));
        $info = $this->service($httpClientDown, $cache)->getExchangeRateInfo();

        $this->assertSame(610.5, $info['rate']);
    }
}
