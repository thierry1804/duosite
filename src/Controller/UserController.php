<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Quote;
use App\Form\UserType;
use App\Repository\QuoteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use App\Service\PdfGenerator;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/register', name: 'app_user_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe est fourni, le hasher
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/quotes', name: 'app_user_quotes', methods: ['GET'])]
    public function quotes(QuoteRepository $quoteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $quotes = $quoteRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('user/quotes.html.twig', [
            'quotes' => $quotes,
        ]);
    }

    #[Route('/quotes/{id}', name: 'app_user_quote_show', methods: ['GET'])]
    public function showQuote(Quote $quote): Response
    {
        $user = $this->getUser();
        if (!$user || $quote->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }

        return $this->render('user/quote_show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/quotes/{id}/view-pdf', name: 'app_user_quotes_view_pdf', methods: ['GET'])]
    public function viewPdf(Quote $quote): Response
    {
        $user = $this->getUser();
        if (!$user || $quote->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }

        if (!in_array($quote->getStatus(), ['completed', 'accepted'])) {
            throw $this->createAccessDeniedException('Cette offre n\'est pas encore disponible.');
        }

        // Récupérer la dernière offre envoyée
        $offer = $quote->getOffers()->last();
        if (!$offer || !$offer->getPdfFilePath()) {
            throw $this->createAccessDeniedException('Le PDF de l\'offre n\'est pas disponible.');
        }

        // Vérifier que le fichier existe
        $pdfPath = $this->getParameter('kernel.project_dir') . '/public/' . $offer->getPdfFilePath();
        if (!file_exists($pdfPath)) {
            throw $this->createAccessDeniedException('Le PDF de l\'offre n\'est pas disponible.');
        }

        // Lire et renvoyer le fichier PDF
        return new Response(
            file_get_contents($pdfPath),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="offre-' . $quote->getQuoteNumber() . '.pdf"'
            ]
        );
    }

    #[Route('/quotes/{id}/offer', name: 'app_user_quotes_offer', methods: ['GET'])]
    public function viewOffer(Quote $quote): Response
    {
        $user = $this->getUser();
        if (!$user || $quote->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }

        if (!in_array($quote->getStatus(), ['completed', 'accepted'])) {
            throw $this->createAccessDeniedException('Cette offre n\'est pas encore disponible.');
        }

        return $this->render('user/quote_offer.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/quotes/{id}/accept', name: 'app_user_quotes_accept', methods: ['POST'])]
    public function acceptQuote(Quote $quote, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user || $quote->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }

        if ($quote->getStatus() !== 'completed') {
            throw $this->createAccessDeniedException('Cette offre n\'est pas encore disponible.');
        }

        // Récupérer la dernière offre envoyée
        $offer = $quote->getOffers()->last();
        if (!$offer) {
            throw $this->createAccessDeniedException('Aucune offre disponible.');
        }

        // Mettre à jour le statut de l'offre
        $offer->setStatus('accepted');
        
        // Mettre à jour le statut de la demande de devis
        $quote->setStatus('accepted');
        
        $entityManager->flush();

        // Envoyer un email de notification à l'admin
        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Système de gestion des devis'))
            ->to('commercial@duoimport.mg')
            ->subject('Offre acceptée - ' . $quote->getQuoteNumber())
            ->html($this->renderView('emails/quote_offer_accepted.html.twig', [
                'quote' => $quote,
                'user' => $user
            ]));

        $mailer->send($email);

        $this->addFlash('success', 'Vous avez accepté l\'offre avec succès.');
        return $this->redirectToRoute('app_user_quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/quotes/{id}/reject', name: 'app_user_quotes_reject', methods: ['POST'])]
    public function rejectQuote(Quote $quote, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user || $quote->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }

        if ($quote->getStatus() !== 'completed') {
            throw $this->createAccessDeniedException('Cette offre n\'est pas encore disponible.');
        }

        // Récupérer la dernière offre envoyée
        $offer = $quote->getOffers()->last();
        if (!$offer) {
            throw $this->createAccessDeniedException('Aucune offre disponible.');
        }

        // Mettre à jour le statut de l'offre
        $offer->setStatus('declined');
        
        // Remettre la demande de devis en cours
        $quote->setStatus('in_progress');
        
        $entityManager->flush();

        // Envoyer un email de notification à l'admin
        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Système de gestion des devis'))
            ->to('commercial@duoimport.mg')
            ->subject('Offre refusée - ' . $quote->getQuoteNumber())
            ->html($this->renderView('emails/quote_offer_rejected.html.twig', [
                'quote' => $quote,
                'user' => $user
            ]));

        $mailer->send($email);

        $this->addFlash('success', 'Vous avez refusé l\'offre. La demande de devis est remise en cours de traitement.');
        return $this->redirectToRoute('app_user_quote_show', ['id' => $quote->getId()]);
    }
} 