<?php

namespace App\Repository;

use App\Entity\LegalPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegalPage>
 */
class LegalPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalPage::class);
    }

    public function findOneBySlug(string $slug): ?LegalPage
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function getOrCreate(string $slug, string $defaultTitle = ''): LegalPage
    {
        $page = $this->findOneBySlug($slug);
        if ($page) {
            return $page;
        }

        $page = new LegalPage();
        $page->setSlug($slug);
        $page->setTitle($defaultTitle !== '' ? $defaultTitle : $slug);
        $page->setContent('');
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush();

        return $page;
    }
}
