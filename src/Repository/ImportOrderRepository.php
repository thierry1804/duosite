<?php

namespace App\Repository;

use App\Entity\ImportOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportOrder>
 */
class ImportOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportOrder::class);
    }

    public function findOneByTrackingToken(string $token): ?ImportOrder
    {
        return $this->findOneBy(['trackingToken' => $token], []);
    }

    /**
     * @return ImportOrder[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
