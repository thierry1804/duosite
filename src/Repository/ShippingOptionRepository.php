<?php

namespace App\Repository;

use App\Entity\ShippingOption;
use App\Entity\QuoteOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingOption>
 *
 * @method ShippingOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingOption[]    findAll()
 * @method ShippingOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShippingOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingOption::class);
    }

    public function save(ShippingOption $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShippingOption $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve toutes les options d'expédition associées à une offre
     *
     * @param QuoteOffer $offer
     * @return ShippingOption[]
     */
    public function findByOffer(QuoteOffer $offer): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.offer = :offer')
            ->setParameter('offer', $offer)
            ->orderBy('s.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 