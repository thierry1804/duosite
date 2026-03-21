<?php

namespace App\Repository;

use App\Entity\ImportOrder;
use App\Entity\ImportOrderStatusHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportOrderStatusHistory>
 */
class ImportOrderStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportOrderStatusHistory::class);
    }

    /**
     * @return ImportOrderStatusHistory[]
     */
    public function findByImportOrder(ImportOrder $order): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.importOrder = :order')
            ->setParameter('order', $order)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
