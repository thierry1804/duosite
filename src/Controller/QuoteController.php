<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Form\QuoteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class QuoteController extends AbstractController
{
    #[Route('/quote', name: 'app_quote')]
    public function index(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $quote = new Quote();
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde dans la base de données
            $entityManager->persist($quote);
            $entityManager->flush();

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
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Récupération des devis non traités
        $quoteRepository = $entityManager->getRepository(Quote::class);
        $pendingQuotes = $quoteRepository->findByProcessed(false);
        $processedQuotes = $quoteRepository->findByProcessed(true);
        
        return $this->render('quote/dashboard.html.twig', [
            'pendingQuotes' => $pendingQuotes,
            'processedQuotes' => $processedQuotes,
        ]);
    }

    #[Route('/quote/{id}/process', name: 'app_quote_process')]
    public function processQuote(Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Marquer le devis comme traité
        $quote->setProcessed(true);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le devis a été marqué comme traité.');
        return $this->redirectToRoute('app_quote_dashboard');
    }

    #[Route('/quote/{id}/view', name: 'app_quote_view')]
    public function viewQuote(Quote $quote): Response
    {
        return $this->render('quote/view.html.twig', [
            'quote' => $quote,
        ]);
    }
} 