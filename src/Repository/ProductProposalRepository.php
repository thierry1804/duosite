<?php

namespace App\Repository;

use App\Entity\ProductProposal;
use App\Entity\QuoteOffer;
use App\Entity\QuoteItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductProposal>
 *
 * @method ProductProposal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductProposal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductProposal[]    findAll()
 * @method ProductProposal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductProposalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductProposal::class);
    }

    public function save(ProductProposal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductProposal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve toutes les propositions associées à une offre
     *
     * @param QuoteOffer $offer
     * @return ProductProposal[]
     */
    public function findByOffer(QuoteOffer $offer): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.offer = :offer')
            ->setParameter('offer', $offer)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les propositions associées à un élément de devis
     *
     * @param QuoteItem $quoteItem
     * @return ProductProposal[]
     */
    public function findByQuoteItem(QuoteItem $quoteItem): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.quoteItem = :quoteItem')
            ->setParameter('quoteItem', $quoteItem)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 