<?php

namespace App\Repository;

use App\Entity\QuoteStatusHistory;
use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteStatusHistory>
 */
class QuoteStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteStatusHistory::class);
    }

    /**
     * Récupère l'historique des statuts pour un devis donné, trié par date de création (plus récent en premier)
     */
    public function findByQuote(Quote $quote): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.quote = :quote')
            ->setParameter('quote', $quote)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'historique des statuts pour un devis donné, trié par date de création (plus ancien en premier)
     */
    public function findByQuoteOrderedAsc(Quote $quote): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.quote = :quote')
            ->setParameter('quote', $quote)
            ->orderBy('h.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le dernier changement de statut pour un devis donné
     */
    public function findLastByQuote(Quote $quote): ?QuoteStatusHistory
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.quote = :quote')
            ->setParameter('quote', $quote)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les changements de statut effectués par un utilisateur donné
     */
    public function findByChangedBy(string $changedBy): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.changedBy = :changedBy')
            ->setParameter('changedBy', $changedBy)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les changements de statut dans une période donnée
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.createdAt >= :startDate')
            ->andWhere('h.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
