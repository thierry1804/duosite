<?php

namespace App\Repository;

use App\Entity\QuoteOffer;
use App\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteOffer>
 *
 * @method QuoteOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteOffer[]    findAll()
 * @method QuoteOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteOffer::class);
    }

    public function save(QuoteOffer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuoteOffer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve toutes les offres associées à un devis
     *
     * @param Quote $quote
     * @return QuoteOffer[]
     */
    public function findByQuote(Quote $quote): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.quote = :quote')
            ->setParameter('quote', $quote)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les offres avec le statut spécifié
     *
     * @param string $status
     * @return QuoteOffer[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
