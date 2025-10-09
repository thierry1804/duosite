<?php

namespace App\Repository;

use App\Entity\Quote;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quote>
 *
 * @method Quote|null find($id, $lockMode = null, $lockVersion = null)
 * @method Quote|null findOneBy(array $criteria, array $orderBy = null)
 * @method Quote[]    findAll()
 * @method Quote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * @return Quote[] Returns an array of Quote objects
     */
    public function findByProcessed(bool $processed): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.processed = :processed')
            ->setParameter('processed', $processed)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Quote[] Returns an array of Quote objects
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de devis pour un utilisateur donné
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Quote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un devis avec ses offres pré-chargées
     */
    public function findOneWithOffers(int $id): ?Quote
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.offers', 'o')
            ->addSelect('o')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Charge un devis avec ses offres
     */
    public function loadWithOffers(int $id): ?Quote
    {
        return $this->createQueryBuilder('q')
            ->select('q', 'o')
            ->leftJoin('q.offers', 'o')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 