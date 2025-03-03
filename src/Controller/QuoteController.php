<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Form\QuoteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class QuoteController extends AbstractController
{
    #[Route('/quote', name: 'app_quote')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $quote = new Quote();
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Création de l'email
            $email = (new Email())
                ->from($quote->getEmail())
                ->to('commercial@duoimport.mg')
                ->subject('Nouvelle demande de devis')
                ->html($this->renderView(
                    'emails/quote.html.twig',
                    ['quote' => $quote]
                ));

            // Envoi de l'email
            $mailer->send($email);

            $this->addFlash('success', 'Votre demande de devis a été envoyée avec succès !');
            return $this->redirectToRoute('app_quote');
        }
        
        return $this->render('quote/index.html.twig', [
            'form' => $form->createView(),
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