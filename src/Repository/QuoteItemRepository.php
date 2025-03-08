<?php

namespace App\Repository;

use App\Entity\QuoteItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteItem>
 *
 * @method QuoteItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuoteItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuoteItem[]    findAll()
 * @method QuoteItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuoteItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteItem::class);
    }

    public function save(QuoteItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuoteItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 