<?php

namespace App\Controller;

use App\Entity\LegalPage;
use App\Repository\LegalPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/privacy-policy', name: 'app_privacy_policy')]
    public function privacyPolicy(LegalPageRepository $legalPageRepository): Response
    {
        $legalPage = $legalPageRepository->getOrCreate(
            LegalPage::SLUG_PRIVACY,
            'Politique de confidentialité'
        );

        return $this->render('page/privacy_policy.html.twig', [
            'legalPage' => $legalPage,
        ]);
    }
}
