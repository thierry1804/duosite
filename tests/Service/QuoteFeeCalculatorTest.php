<?php

namespace App\Tests\Service;

use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Entity\QuoteSettings;
use App\Entity\User;
use App\Repository\QuoteSettingsRepository;
use App\Service\QuoteFeeCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteFeeCalculatorTest extends TestCase
{
    private QuoteSettingsRepository&MockObject $settingsRepository;
    private QuoteFeeCalculator $calculator;

    protected function setUp(): void
    {
        $this->settingsRepository = $this->createMock(QuoteSettingsRepository::class);
        $this->calculator = new QuoteFeeCalculator($this->settingsRepository);
    }

    private function quoteWithItems(int $itemCount): Quote
    {
        $quote = new Quote();
        for ($i = 0; $i < $itemCount; $i++) {
            $quote->addItem(new QuoteItem());
        }

        return $quote;
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($user, $id);

        return $user;
    }

    public function testNoFeeWhenWithinFreeLimit(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $quote = $this->quoteWithItems(2);

        $result = $this->calculator->calculateFee($quote);

        $this->assertSame(0, $result['totalFee']);
        $this->assertFalse($result['paymentRequired']);
        $this->assertSame('not_required', $quote->getPaymentStatus());
    }

    public function testFeeChargedForItemsBeyondFreeLimit(): void
    {
        $settings = new QuoteSettings();
        $settings->setItemPrice(1500);
        $this->settingsRepository->method('getSettings')->willReturn($settings);

        $quote = $this->quoteWithItems(5); // 2 gratuits + 3 payants
        $result = $this->calculator->calculateFee($quote);

        $this->assertSame(3, $result['paidItems']);
        $this->assertSame(4500, $result['totalFee']);
        $this->assertTrue($result['paymentRequired']);
        $this->assertSame('pending', $quote->getPaymentStatus());
    }

    public function testFallsBackToDefaultItemPriceWhenSettingsRowHasDefaultPrice(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $quote = $this->quoteWithItems(3); // 1 payant

        $result = $this->calculator->calculateFee($quote);

        $this->assertSame(2000, $result['itemPrice']);
        $this->assertSame(2000, $result['totalFee']);
    }

    public function testKeepsCompletedPaymentStatusOnRecalculation(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $quote = $this->quoteWithItems(4);
        $quote->setPaymentStatus('completed');

        $this->calculator->calculateFee($quote);

        $this->assertSame('completed', $quote->getPaymentStatus());
    }

    public function testIsFirstQuoteTrueWhenUserHasOnlyThisQuote(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $user = $this->userWithId(42);
        $quote = $this->quoteWithItems(3);
        $quote->setUser($user);

        $result = $this->calculator->calculateFee($quote, [42 => 1]);

        $this->assertTrue($result['isFirstQuote']);
    }

    public function testIsFirstQuoteFalseWhenUserHasSeveralQuotes(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $user = $this->userWithId(42);
        $quote = $this->quoteWithItems(3);
        $quote->setUser($user);

        $result = $this->calculator->calculateFee($quote, [42 => 3]);

        $this->assertFalse($result['isFirstQuote']);
    }

    public function testIsFirstQuoteDefaultsTrueWhenNoCountMapProvided(): void
    {
        $this->settingsRepository->method('getSettings')->willReturn(new QuoteSettings());
        $user = $this->userWithId(42);
        $quote = $this->quoteWithItems(3);
        $quote->setUser($user);

        $result = $this->calculator->calculateFee($quote);

        $this->assertTrue($result['isFirstQuote']);
    }
}
