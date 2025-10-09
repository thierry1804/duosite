<?php

namespace App\Repository;

use App\Entity\QuoteSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteSettings>
 */
class QuoteSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteSettings::class);
    }

    public function getSettings(): QuoteSettings
    {
        $settings = $this->findOneBy([]);
        
        if (!$settings) {
            $settings = new QuoteSettings();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }
        
        return $settings;
    }
} 