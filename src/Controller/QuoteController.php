<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuoteController extends AbstractController
{
    #[Route('/quote', name: 'app_quote')]
    public function index(Request $request): Response
    {
        // Le formulaire de demande de devis sera implémenté ultérieurement
        
        return $this->render('quote/index.html.twig', [
            'controller_name' => 'QuoteController',
        ]);
    }
    
    #[Route('/quote/dashboard', name: 'app_quote_dashboard')]
    public function dashboard(): Response
    {
        // L'espace client pour suivre les devis sera implémenté ultérieurement
        
        return $this->render('quote/dashboard.html.twig', [
            'controller_name' => 'QuoteController',
        ]);
    }
} 