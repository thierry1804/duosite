<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CGVController extends AbstractController
{
    #[Route('/cgv', name: 'app_cgv')]
    public function index(): Response
    {
        return $this->render('cgv/index.html.twig');
    }
} 