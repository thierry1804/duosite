<?php

namespace App\Repository;

use App\Entity\SiteContactSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteContactSettings>
 */
class SiteContactSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContactSettings::class);
    }

    public function getSettings(): SiteContactSettings
    {
        $settings = $this->createQueryBuilder('s')
            ->leftJoin('s.phones', 'p')->addSelect('p')
            ->leftJoin('s.openingHours', 'h')->addSelect('h')
            ->leftJoin('s.socialLinks', 'l')->addSelect('l')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$settings) {
            $settings = SiteContactSettings::createWithDefaults();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}
