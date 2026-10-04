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
        // findOneBy + lazy-load : évite les JOINs qui tronquent les collections
        // (setMaxResults / produit cartésien) et les doublons à la sauvegarde.
        $settings = $this->findOneBy([]);

        if (!$settings) {
            $settings = SiteContactSettings::createWithDefaults();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();

            return $settings;
        }

        // Force le chargement complet des collections
        $settings->getPhones()->toArray();
        $settings->getOpeningHours()->toArray();
        $settings->getSocialLinks()->toArray();

        return $settings;
    }
}
