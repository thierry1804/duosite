<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\QuoteStatusHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class QuoteTrackerService
{
    // Statuts valides pour les devis
    public const VALID_STATUSES = [
        'new' => 'Nouveau',
        'pending' => 'En attente',
        'viewed' => 'Consulté',
        'in_progress' => 'En cours de traitement',
        'waiting_customer' => 'En attente du client',
        'accepted' => 'Accepté',
        'declined' => 'Refusé',
        'completed' => 'Terminé',
        'rejected' => 'Rejeté',
        'converted' => 'Converti en commande',
        'shipped' => 'Expédié',
        'delivered' => 'Livré',
        'canceled' => 'Annulé'
    ];

    // Transitions autorisées (statut actuel => [statuts possibles])
    public const ALLOWED_TRANSITIONS = [
        'new' => ['pending', 'viewed', 'canceled'],
        'pending' => ['viewed', 'in_progress', 'rejected', 'canceled'],
        'viewed' => ['in_progress', 'pending', 'canceled'],
        'in_progress' => ['waiting_customer', 'completed', 'rejected', 'canceled'],
        'waiting_customer' => ['accepted', 'declined', 'in_progress', 'canceled'],
        'accepted' => ['converted', 'in_progress', 'canceled'],
        'declined' => ['in_progress', 'canceled'],
        'completed' => ['converted', 'shipped', 'canceled'],
        'rejected' => ['in_progress', 'canceled'],
        'converted' => ['shipped', 'delivered', 'canceled'],
        'shipped' => ['delivered', 'canceled'],
        'delivered' => [],
        'canceled' => ['pending', 'in_progress']
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private TokenStorageInterface $tokenStorage
    ) {}

    /**
     * Change le statut d'un devis avec validation et historique
     */
    public function changeStatus(
        Quote $quote, 
        string $newStatus, 
        ?string $comment = null, 
        ?string $changedBy = null
    ): Quote {
        $oldStatus = $quote->getStatus();
        
        // Vérifier si le statut est valide
        if (!$this->isValidStatus($newStatus)) {
            throw new \InvalidArgumentException(sprintf('Statut invalide: %s', $newStatus));
        }
        
        // Vérifier si la transition est autorisée
        if (!$this->isTransitionAllowed($oldStatus, $newStatus)) {
            throw new \InvalidArgumentException(
                sprintf('Transition non autorisée de "%s" vers "%s"', $oldStatus, $newStatus)
            );
        }
        
        // Si le statut est identique, ne rien faire
        if ($oldStatus === $newStatus) {
            return $quote;
        }

        // Déterminer qui a effectué le changement
        if ($changedBy === null) {
            $user = $this->tokenStorage->getToken()?->getUser();
            $changedBy = $user ? $user->getEmail() : 'system';
        }

        // Changer le statut
        $quote->setStatus($newStatus);

        // Créer l'entrée d'historique
        $history = new QuoteStatusHistory();
        $history->setQuote($quote);
        $history->setOldStatus($oldStatus);
        $history->setNewStatus($newStatus);
        $history->setChangedBy($changedBy);
        $history->setComment($comment);

        // Persister les changements
        $this->entityManager->persist($history);
        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        // Dispatcher l'événement pour les notifications
        $event = new \App\Event\QuoteStatusChangedEvent($quote, $oldStatus, $newStatus, $changedBy, $comment);
        $this->eventDispatcher->dispatch($event);

        return $quote;
    }

    /**
     * Récupère l'historique complet des changements de statut pour un devis
     */
    public function getStatusHistory(Quote $quote): array
    {
        $repository = $this->entityManager->getRepository(QuoteStatusHistory::class);
        return $repository->findByQuoteOrderedAsc($quote);
    }

    /**
     * Récupère le dernier changement de statut pour un devis
     */
    public function getLastStatusChange(Quote $quote): ?QuoteStatusHistory
    {
        $repository = $this->entityManager->getRepository(QuoteStatusHistory::class);
        return $repository->findLastByQuote($quote);
    }

    /**
     * Vérifie si un statut est valide
     */
    public function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::VALID_STATUSES);
    }

    /**
     * Vérifie si une transition entre deux statuts est autorisée
     */
    public function isTransitionAllowed(string $fromStatus, string $toStatus): bool
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$fromStatus])) {
            return false;
        }
        
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus]);
    }

    /**
     * Retourne tous les statuts valides
     */
    public function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    /**
     * Retourne les transitions possibles depuis un statut donné
     */
    public function getPossibleTransitions(string $fromStatus): array
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$fromStatus])) {
            return [];
        }
        
        $possibleStatuses = self::ALLOWED_TRANSITIONS[$fromStatus];
        $result = [];
        
        foreach ($possibleStatuses as $status) {
            $result[$status] = self::VALID_STATUSES[$status];
        }
        
        return $result;
    }

    /**
     * Retourne toutes les transitions autorisées
     */
    public function getAllowedTransitions(): array
    {
        return self::ALLOWED_TRANSITIONS;
    }

    /**
     * Retourne les libellés des statuts
     */
    public function getStatusLabels(): array
    {
        return self::VALID_STATUSES;
    }

    /**
     * Retourne le libellé d'un statut
     */
    public function getStatusLabel(string $status): string
    {
        return self::VALID_STATUSES[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Crée un historique initial pour un nouveau devis
     */
    public function createInitialHistory(Quote $quote, ?string $changedBy = null): void
    {
        if ($changedBy === null) {
            $user = $this->tokenStorage->getToken()?->getUser();
            $changedBy = $user ? $user->getEmail() : 'system';
        }

        $history = new QuoteStatusHistory();
        $history->setQuote($quote);
        $history->setOldStatus('new');
        $history->setNewStatus($quote->getStatus());
        $history->setChangedBy($changedBy);
        $history->setComment('Devis créé');

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    /**
     * Récupère les statistiques des changements de statut
     */
    public function getStatusChangeStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $repository = $this->entityManager->getRepository(QuoteStatusHistory::class);
        $changes = $repository->findByDateRange($startDate, $endDate);
        
        $stats = [
            'total_changes' => count($changes),
            'by_status' => [],
            'by_user' => [],
            'by_day' => []
        ];
        
        foreach ($changes as $change) {
            $newStatus = $change->getNewStatus();
            $changedBy = $change->getChangedBy() ?? 'system';
            $day = $change->getCreatedAt()->format('Y-m-d');
            
            // Statistiques par statut
            $stats['by_status'][$newStatus] = ($stats['by_status'][$newStatus] ?? 0) + 1;
            
            // Statistiques par utilisateur
            $stats['by_user'][$changedBy] = ($stats['by_user'][$changedBy] ?? 0) + 1;
            
            // Statistiques par jour
            $stats['by_day'][$day] = ($stats['by_day'][$day] ?? 0) + 1;
        }
        
        return $stats;
    }

    /**
     * Retourne l'EntityManager pour les opérations externes
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
