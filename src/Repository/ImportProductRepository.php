<?php

namespace App\Repository;

use App\Entity\ImportProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportProduct>
 */
class ImportProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportProduct::class);
    }

    public function findOneByCode(string $code): ?ImportProduct
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.active = :active')
            ->setParameter('code', $code)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ImportProduct[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
