<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\User;
use App\Repository\QuoteRepository;
use App\Repository\QuoteSettingsRepository;

class QuoteFeeCalculator
{
    private QuoteRepository $quoteRepository;
    private QuoteSettingsRepository $settingsRepository;

    public function __construct(
        QuoteRepository $quoteRepository,
        QuoteSettingsRepository $settingsRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->settingsRepository = $settingsRepository;
    }

    public function calculateFee(Quote $quote): array
    {
        $settings = $this->settingsRepository->getSettings();
        $freeItemsLimit = $settings->getFreeItemsLimit();
        $itemPrice = $settings->getItemPrice();
        
        $user = $quote->getUser();
        $itemCount = count($quote->getItems());
        
        // Si c'est le premier devis de l'utilisateur
        if ($this->isFirstQuote($user)) {
            // Les premiers X articles sont gratuits
            if ($itemCount <= $freeItemsLimit) {
                return [
                    'totalFee' => 0,
                    'itemCount' => $itemCount,
                    'freeItems' => $itemCount,
                    'paidItems' => 0,
                    'itemPrice' => $itemPrice,
                    'isFirstQuote' => true,
                    'freeItemsLimit' => $freeItemsLimit
                ];
            } else {
                // Au-delà du plafond, les articles supplémentaires sont payants
                $paidItems = $itemCount - $freeItemsLimit;
                $totalFee = $paidItems * $itemPrice;
                
                return [
                    'totalFee' => $totalFee,
                    'itemCount' => $itemCount,
                    'freeItems' => $freeItemsLimit,
                    'paidItems' => $paidItems,
                    'itemPrice' => $itemPrice,
                    'isFirstQuote' => true,
                    'freeItemsLimit' => $freeItemsLimit
                ];
            }
        } else {
            // Pour tous les autres devis, tous les articles sont payants
            $totalFee = $itemCount * $itemPrice;
            
            return [
                'totalFee' => $totalFee,
                'itemCount' => $itemCount,
                'freeItems' => 0,
                'paidItems' => $itemCount,
                'itemPrice' => $itemPrice,
                'isFirstQuote' => false,
                'freeItemsLimit' => $freeItemsLimit
            ];
        }
    }

    private function isFirstQuote(?User $user): bool
    {
        if (!$user) {
            return true; // Si pas d'utilisateur, on considère que c'est le premier devis
        }
        
        // Compter les devis de cet utilisateur
        $quotesCount = $this->quoteRepository->countByUser($user);
        
        // Si c'est le premier devis (en comptant le devis actuel)
        return $quotesCount <= 1;
    }
} 