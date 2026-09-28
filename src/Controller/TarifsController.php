<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TarifsController extends AbstractController
{
    #[Route('/tarifs', name: 'app_tarifs')]
    public function index(): Response
    {
        return $this->render('tarifs/index.html.twig', [
            'controller_name' => 'TarifsController',
        ]);
    }
}
