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

    /** Cache des paramètres pour éviter une requête par devis (même requête). */
    private ?\App\Entity\QuoteSettings $cachedSettings = null;

    public function __construct(
        QuoteRepository $quoteRepository,
        QuoteSettingsRepository $settingsRepository
    ) {
        $this->quoteRepository = $quoteRepository;
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
        $freeItemsLimit = $settings ? $settings->getFreeItemsLimit() : 3;
        $itemPrice = $settings ? $settings->getItemPrice() : 5000;
        
        $user = $quote->getUser();
        $itemCount = count($quote->getItems());
        
        // Si c'est le premier devis de l'utilisateur
        if ($this->isFirstQuote($user, $userQuotesCountMap)) {
            // Les premiers X articles sont gratuits
            if ($itemCount <= $freeItemsLimit) {
                // Pas de frais à payer
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
            } else {
                // Au-delà du plafond, les articles supplémentaires sont payants
                $paidItems = $itemCount - $freeItemsLimit;
                $totalFee = $paidItems * $itemPrice;
                
                // Des frais sont à payer
                // Important : s'assurer que le statut de paiement est correctement défini
                if ($quote->getPaymentStatus() === 'completed') {
                    // Conserver le statut "completed" si le paiement a déjà été confirmé
                    // Ne pas changer vers "pending"
                } else {
                    $quote->setPaymentStatus('pending');
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
        } else {
            // Pour tous les autres devis, tous les articles sont payants
            $totalFee = $itemCount * $itemPrice;
            
            // Des frais sont à payer si le montant est supérieur à zéro
            if ($totalFee > 0) {
                // Important : s'assurer que le statut de paiement est correctement défini
                if ($quote->getPaymentStatus() === 'completed') {
                    // Conserver le statut "completed" si le paiement a déjà été confirmé
                    // Ne pas changer vers "pending"
                } else {
                    $quote->setPaymentStatus('pending');
                }
                $paymentRequired = true;
            } else {
                $quote->setPaymentStatus('not_required');
                $paymentRequired = false;
            }
            
            return [
                'totalFee' => $totalFee,
                'itemCount' => $itemCount,
                'freeItems' => 0,
                'paidItems' => $itemCount,
                'itemPrice' => $itemPrice,
                'isFirstQuote' => false,
                'freeItemsLimit' => $freeItemsLimit,
                'paymentRequired' => $paymentRequired
            ];
        }
    }

    /**
     * @param array<int, int>|null $userQuotesCountMap [user_id => count] pré-calculé pour éviter N+1
     */
    private function isFirstQuote(?User $user, ?array $userQuotesCountMap = null): bool
    {
        if (!$user) {
            return true; // Si pas d'utilisateur, on considère que c'est le premier devis
        }
        
        if ($userQuotesCountMap !== null) {
            $quotesCount = $userQuotesCountMap[$user->getId()] ?? 0;
        } else {
            $quotesCount = $this->quoteRepository->countByUser($user);
        }
        
        return $quotesCount <= 1;
    }
} 