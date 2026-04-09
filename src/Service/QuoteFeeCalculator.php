<?php

namespace App\Service;

use App\Entity\Quote;
use App\Repository\QuoteSettingsRepository;

class QuoteFeeCalculator
{
    private QuoteSettingsRepository $settingsRepository;

    /** Cache des paramètres pour éviter une requête par devis (même requête). */
    private ?\App\Entity\QuoteSettings $cachedSettings = null;

    public function __construct(
        QuoteSettingsRepository $settingsRepository
    ) {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @param array<int, int>|null $userQuotesCountMap [user_id => nombre de devis] pour éviter N+1 (optionnel)
     */
    public function calculateFee(Quote $quote, ?array $userQuotesCountMap = null): array
    {
        // Paramètres mis en cache pour toute la requête
        if ($this->cachedSettings === null) {
            $this->cachedSettings = $this->settingsRepository->getSettings();
        }
        $settings = $this->cachedSettings;
        // Règle métier fixe: 2 articles gratuits maximum par demande.
        $freeItemsLimit = 2;
        $itemPrice = $settings ? $settings->getItemPrice() : 5000;
        $itemCount = count($quote->getItems());

        if ($itemCount <= $freeItemsLimit) {
            $quote->setPaymentStatus('not_required');

            return [
                'totalFee' => 0,
                'itemCount' => $itemCount,
                'freeItems' => $itemCount,
                'paidItems' => 0,
                'itemPrice' => $itemPrice,
                'isFirstQuote' => true,
                'freeItemsLimit' => $freeItemsLimit,
                'paymentRequired' => false
            ];
        }

        $paidItems = $itemCount - $freeItemsLimit;
        $totalFee = $paidItems * $itemPrice;

        if ($quote->getPaymentStatus() !== 'completed') {
            $quote->setPaymentStatus('pending');
        } else {
            $quote->setPaymentStatus('completed');
        }

        return [
            'totalFee' => $totalFee,
            'itemCount' => $itemCount,
            'freeItems' => $freeItemsLimit,
            'paidItems' => $paidItems,
            'itemPrice' => $itemPrice,
            'isFirstQuote' => true,
            'freeItemsLimit' => $freeItemsLimit,
            'paymentRequired' => true
        ];
    }
} 