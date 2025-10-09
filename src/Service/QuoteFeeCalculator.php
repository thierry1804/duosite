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
        // Récupérer les paramètres, avec des valeurs par défaut si non configurés
        $settings = $this->settingsRepository->getSettings();
        $freeItemsLimit = $settings ? $settings->getFreeItemsLimit() : 3;
        $itemPrice = $settings ? $settings->getItemPrice() : 5000;
        
        $user = $quote->getUser();
        $itemCount = count($quote->getItems());
        
        // Si c'est le premier devis de l'utilisateur
        if ($this->isFirstQuote($user)) {
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