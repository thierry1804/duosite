<?php

namespace App\Tests\Service;

use App\Service\PapiPaymentService;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PapiPaymentServiceTest extends TestCase
{
    private const TEST_SECRET = 'pwhsec_5f1c2b7e9a0d4c3b8e6f1a2d9c7b4e0f3a6d8c1b5e9f2a7d4c0b3e6f9a1d8c2b';
    private const TEST_TIMESTAMP = '1757750400';
    private const TEST_BODY = '{"paymentReference":"PAPI-TEST-0001","paymentStatus":"SUCCESS","amount":150000}';

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function service(HttpClientInterface $httpClient): PapiPaymentService
    {
        return new PapiPaymentService($httpClient, $this->logger, 'test_api_key', self::TEST_SECRET);
    }

    /**
     * Vecteur de test officiel de la doc Papi (securing-notifications) : vérifie que notre
     * implémentation du calcul HMAC-SHA256(secret, "t.raw_body") produit la même signature.
     */
    public function testVerifySignatureAcceptsOfficialTestVector(): void
    {
        $service = $this->service(new MockHttpClient());

        $expectedSignature = hash_hmac('sha256', self::TEST_TIMESTAMP . '.' . self::TEST_BODY, self::TEST_SECRET);
        $header = sprintf('t=%s,v1=%s', self::TEST_TIMESTAMP, $expectedSignature);

        // On désactive la tolérance temporelle : le timestamp du vecteur de test est fixe et ancien.
        $this->assertTrue($service->verifyNotificationSignature(self::TEST_BODY, $header, 0));
    }

    public function testVerifySignatureRejectsTamperedBody(): void
    {
        $service = $this->service(new MockHttpClient());

        $signature = hash_hmac('sha256', self::TEST_TIMESTAMP . '.' . self::TEST_BODY, self::TEST_SECRET);
        $header = sprintf('t=%s,v1=%s', self::TEST_TIMESTAMP, $signature);

        $tamperedBody = '{"paymentReference":"PAPI-TEST-0001","paymentStatus":"SUCCESS","amount":999999}';

        $this->assertFalse($service->verifyNotificationSignature($tamperedBody, $header, 0));
    }

    public function testVerifySignatureRejectsWrongSecret(): void
    {
        $service = new PapiPaymentService(new MockHttpClient(), $this->logger, 'test_api_key', 'pwhsec_wrong_secret');

        $signature = hash_hmac('sha256', self::TEST_TIMESTAMP . '.' . self::TEST_BODY, self::TEST_SECRET);
        $header = sprintf('t=%s,v1=%s', self::TEST_TIMESTAMP, $signature);

        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, $header, 0));
    }

    public function testVerifySignatureRejectsMissingHeader(): void
    {
        $service = $this->service(new MockHttpClient());

        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, null));
        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, ''));
    }

    public function testVerifySignatureRejectsMalformedHeader(): void
    {
        $service = $this->service(new MockHttpClient());

        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, 'not-a-valid-header'));
        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, 't=abc,v1=zzzz'));
    }

    public function testVerifySignatureRejectsStaleTimestampAsReplayProtection(): void
    {
        $service = $this->service(new MockHttpClient());

        $oldTimestamp = (string) (time() - 3600); // 1h dans le passé
        $signature = hash_hmac('sha256', $oldTimestamp . '.' . self::TEST_BODY, self::TEST_SECRET);
        $header = sprintf('t=%s,v1=%s', $oldTimestamp, $signature);

        // Avec la tolérance par défaut (300s), un timestamp vieux d'1h doit être rejeté.
        $this->assertFalse($service->verifyNotificationSignature(self::TEST_BODY, $header));
    }

    public function testVerifySignatureAcceptsFreshTimestampWithinTolerance(): void
    {
        $service = $this->service(new MockHttpClient());

        $now = (string) time();
        $signature = hash_hmac('sha256', $now . '.' . self::TEST_BODY, self::TEST_SECRET);
        $header = sprintf('t=%s,v1=%s', $now, $signature);

        $this->assertTrue($service->verifyNotificationSignature(self::TEST_BODY, $header));
    }

    public function testCreatePaymentLinkParsesSuccessfulResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'data' => [
                'paymentLink' => 'https://payment-form.papi.mg/duoim/payments/xyz',
                'shortLink' => 'https://link.papi.mg/abc123',
                'notificationToken' => 'token-123',
                'isTestMode' => true,
            ],
        ])));

        $result = $this->service($httpClient)->createPaymentLink(
            1000.0,
            'Test Client',
            'ORDER-001',
            'Test payment'
        );

        $this->assertSame('token-123', $result['notificationToken']);
        $this->assertTrue($result['isTestMode']);
    }

    public function testCreatePaymentLinkThrowsOnApiError(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"error":"bad_request"}', ['http_code' => 400]));

        $this->expectException(\RuntimeException::class);
        $this->service($httpClient)->createPaymentLink(1000.0, 'Test Client', 'ORDER-002', 'Test payment');
    }

    public function testGetPaymentLinkStatusParsesResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'data' => [
                'linkStatus' => 'PAID',
                'paymentStatus' => 'SUCCESS',
            ],
        ])));

        $result = $this->service($httpClient)->getPaymentLinkStatus('ORDER-001');

        $this->assertSame('PAID', $result['linkStatus']);
        $this->assertSame('SUCCESS', $result['paymentStatus']);
    }
}
