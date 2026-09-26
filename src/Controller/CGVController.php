<?php

namespace App\Controller;

use App\Entity\LegalPage;
use App\Repository\LegalPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CGVController extends AbstractController
{
    #[Route('/cgv', name: 'app_cgv')]
    public function index(LegalPageRepository $legalPageRepository): Response
    {
        $legalPage = $legalPageRepository->getOrCreate(
            LegalPage::SLUG_CGV,
            'Conditions Générales de Vente'
        );

        return $this->render('cgv/index.html.twig', [
            'legalPage' => $legalPage,
        ]);
    }
}
