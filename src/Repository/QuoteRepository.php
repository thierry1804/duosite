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
     * Devis récents qui n'ont pas encore d'offre envoyée (statut pending ou in_progress).
     *
     * @return Quote[]
     */
    public function findRecentWithoutOfferSent(int $limit = 5): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'in_progress'])
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Même chose que findRecentWithoutOfferSent mais avec user pré-chargé (évite N+1 sur le dashboard admin).
     *
     * @return Quote[]
     */
    public function findRecentWithoutOfferSentWithUser(int $limit = 5): array
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->addSelect('u')
            ->where('q.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'in_progress'])
            ->orderBy('q.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les devis dont le user_id est dans la liste (avec user pré-chargé).
     * Permet de détecter la fraude pour tous les users en une seule requête.
     *
     * @param int[] $userIds
     * @return Quote[]
     */
    public function findQuotesByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $userIds = array_unique(array_filter($userIds));
        if (empty($userIds)) {
            return [];
        }
        return $this->createQueryBuilder('q')
            ->leftJoin('q.user', 'u')
            ->addSelect('u')
            ->where('q.user IN (:ids)')
            ->setParameter('ids', $userIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Devis « complétés » : terminé, accepté, converti, expédié ou livré.
     *
     * @return Quote[]
     */
    public function findCompletedQuotes(): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.status IN (:statuses)')
            ->setParameter('statuses', ['completed', 'accepted', 'converted', 'shipped', 'delivered'])
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les devis complétés (terminé, accepté, converti, expédié, livré).
     */
    public function countCompleted(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status IN (:statuses)')
            ->setParameter('statuses', ['completed', 'accepted', 'converted', 'shipped', 'delivered'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Devis pour le dashboard avec items et user pré-chargés (évite N+1).
     *
     * @param string[] $statuses un ou plusieurs statuts (ex. ['pending'], ['completed','accepted',...])
     * @return Quote[]
     */
    public function findForDashboard(array $statuses, string $order = 'DESC'): array
    {
        if (empty($statuses)) {
            return [];
        }
        return $this->createQueryBuilder('q')
            ->leftJoin('q.items', 'i')
            ->addSelect('i')
            ->leftJoin('q.user', 'u')
            ->addSelect('u')
            ->where('q.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('q.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les devis par user_id pour une liste d'ids (une seule requête).
     *
     * @param int[] $userIds
     * @return array<int, int> [user_id => nombre de devis]
     */
    public function countByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $userIds = array_unique(array_filter($userIds));
        if (empty($userIds)) {
            return [];
        }
        $rs = $this->createQueryBuilder('q')
            ->select('IDENTITY(q.user) AS uid', 'COUNT(q.id) AS cnt')
            ->where('q.user IN (:ids)')
            ->setParameter('ids', $userIds)
            ->groupBy('q.user')
            ->getQuery()
            ->getArrayResult();
        $map = [];
        foreach ($rs as $row) {
            $map[(int) $row['uid']] = (int) $row['cnt'];
        }
        return $map;
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
     * Compte les devis en attente (status = pending) non traités depuis plus de X jours.
     */
    public function countPendingOlderThanDays(int $days): int
    {
        $limit = (new \DateTimeImmutable())->modify("-{$days} days");

        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status')
            ->andWhere('q.createdAt <= :limit')
            ->setParameter('status', 'pending')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getSingleScalarResult();
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