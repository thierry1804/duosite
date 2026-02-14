<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\User;
use App\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserIdentityTracker
{
    private EntityManagerInterface $entityManager;
    private QuoteRepository $quoteRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuoteRepository $quoteRepository
    ) {
        $this->entityManager = $entityManager;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Trace les informations originales de l'utilisateur dans la demande de devis
     */
    public function traceUserIdentity(Quote $quote): void
    {
        // Stocker les informations originales de l'utilisateur
        $originalData = [
            'firstName' => $quote->getFirstName(),
            'lastName' => $quote->getLastName(),
            'email' => $quote->getEmail(),
            'phone' => $quote->getPhone(),
            'company' => $quote->getCompany(),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        // Utiliser directement la propriété de l'entité
        // Le setter gère la conversion en fonction du type attendu
        $quote->setOriginalUserData($originalData);
    }

    /**
     * Vérifie si l'utilisateur a potentiellement tenté de contourner le système
     * en utilisant différentes identités
     */
    public function detectPotentialFraud(User $user): array
    {
        $quotes = $this->quoteRepository->findBy(['user' => $user]);
        
        $emails = [];
        $phones = [];
        $names = [];
        $suspiciousActivity = false;
        $fraudDetails = [];
        
        foreach ($quotes as $quote) {
            $originalData = $quote->getOriginalUserData();
            if (!$originalData) {
                continue;
            }
            if (is_string($originalData)) {
                $originalData = json_decode($originalData, true) ?: [];
            }
            if (!empty($originalData['email']) && !in_array($originalData['email'], $emails)) {
                $emails[] = $originalData['email'];
            }
            if (!empty($originalData['phone']) && !in_array($originalData['phone'], $phones)) {
                $phones[] = $originalData['phone'];
            }
            $fullName = trim(($originalData['firstName'] ?? '') . ' ' . ($originalData['lastName'] ?? ''));
            if (!empty($fullName) && !in_array($fullName, $names)) {
                $names[] = $fullName;
            }
        }
        
        // Vérifier s'il y a plus de 2 emails ou téléphones différents
        if (count($emails) > 2 || count($phones) > 2) {
            $suspiciousActivity = true;
            $fraudDetails[] = 'Utilisation de multiples identités';
        }
        
        // Vérifier si l'utilisateur a utilisé plus de 2 noms différents
        if (count($names) > 2) {
            $suspiciousActivity = true;
            $fraudDetails[] = 'Changements fréquents de nom';
        }
        
        return $this->buildFraudResult($suspiciousActivity, $emails, $phones, $names, $fraudDetails);
    }

    /**
     * Même logique que detectPotentialFraud mais avec les devis déjà chargés (évite N requêtes).
     *
     * @param Quote[] $quotes devis de l'utilisateur (pré-chargés)
     */
    public function detectPotentialFraudWithQuotes(User $user, array $quotes): array
    {
        $emails = [];
        $phones = [];
        $names = [];
        $suspiciousActivity = false;
        $fraudDetails = [];
        
        foreach ($quotes as $quote) {
            $originalData = $quote->getOriginalUserData();
            
            if (!$originalData) {
                continue;
            }
            
            if (is_string($originalData)) {
                $originalData = json_decode($originalData, true) ?: [];
            }
            
            if (!empty($originalData['email']) && !in_array($originalData['email'], $emails)) {
                $emails[] = $originalData['email'];
            }
            if (!empty($originalData['phone']) && !in_array($originalData['phone'], $phones)) {
                $phones[] = $originalData['phone'];
            }
            $fullName = trim(($originalData['firstName'] ?? '') . ' ' . ($originalData['lastName'] ?? ''));
            if (!empty($fullName) && !in_array($fullName, $names)) {
                $names[] = $fullName;
            }
        }
        
        if (count($emails) > 2 || count($phones) > 2) {
            $suspiciousActivity = true;
            $fraudDetails[] = 'Utilisation de multiples identités';
        }
        if (count($names) > 2) {
            $suspiciousActivity = true;
            $fraudDetails[] = 'Changements fréquents de nom';
        }
        
        return $this->buildFraudResult($suspiciousActivity, $emails, $phones, $names, $fraudDetails);
    }

    private function buildFraudResult(bool $suspiciousActivity, array $emails, array $phones, array $names, array $fraudDetails): array
    {
        $reason = $fraudDetails ? implode(' ; ', $fraudDetails) : '';
        return [
            'suspiciousActivity' => $suspiciousActivity,
            'emails' => $emails,
            'phones' => $phones,
            'names' => $names,
            'details' => $fraudDetails,
            'reason' => $reason,
        ];
    }
} 