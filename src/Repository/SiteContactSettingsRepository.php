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
        // Ne pas utiliser setMaxResults() avec des JOINs : LIMIT 1 coupe les collections
        // (téléphones / horaires / réseaux) et provoque des doublons à la sauvegarde.
        $rows = $this->createQueryBuilder('s')
            ->leftJoin('s.phones', 'p')->addSelect('p')
            ->leftJoin('s.openingHours', 'h')->addSelect('h')
            ->leftJoin('s.socialLinks', 'l')->addSelect('l')
            ->getQuery()
            ->getResult();

        /** @var SiteContactSettings|null $settings */
        $settings = $rows[0] ?? null;

        if (!$settings) {
            $settings = SiteContactSettings::createWithDefaults();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}
