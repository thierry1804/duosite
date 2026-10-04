<?php

namespace App\Repository;

use App\Entity\PasswordResetChallenge;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetChallenge>
 */
class PasswordResetChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetChallenge::class);
    }

    public function findActiveByPublicToken(string $token): ?PasswordResetChallenge
    {
        $challenge = $this->findOneBy(['publicToken' => $token]);
        if (!$challenge || !$challenge->isActive()) {
            return null;
        }

        return $challenge;
    }

    public function invalidateOpenChallengesForUser(User $user): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.consumedAt', ':now')
            ->where('c.user = :user')
            ->andWhere('c.consumedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
