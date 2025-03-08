<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\User;
use App\Form\QuoteType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class QuoteController extends AbstractController
{
    #[Route('/quote', name: 'app_quote', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        MailerInterface $mailer, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response
    {
        $quote = new Quote();
        
        // Si l'utilisateur est connecté, pré-remplir les informations
        $user = $this->getUser();
        if ($user) {
            $quote->setFirstName($user->getFirstName());
            $quote->setLastName($user->getLastName());
            $quote->setEmail($user->getEmail());
            $quote->setPhone($user->getPhone());
            $quote->setCompany($user->getCompany());
            $quote->setUser($user);
        }
        
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Définir le statut initial
                $quote->setStatus('pending');
                
                // Si l'utilisateur n'est pas connecté, vérifier s'il existe déjà ou en créer un nouveau
                if (!$user) {
                    // Vérifier si un utilisateur avec cet email existe déjà
                    $existingUser = $userRepository->findOneBy(['email' => $quote->getEmail()]);
                    
                    if ($existingUser) {
                        // Associer le devis à l'utilisateur existant
                        $quote->setUser($existingUser);
                    } else {
                        // Créer un nouvel utilisateur
                        $newUser = new User();
                        $newUser->setEmail($quote->getEmail());
                        $newUser->setFirstName($quote->getFirstName());
                        $newUser->setLastName($quote->getLastName());
                        $newUser->setPhone($quote->getPhone());
                        $newUser->setCompany($quote->getCompany());
                        
                        // Générer un mot de passe aléatoire
                        $randomPassword = bin2hex(random_bytes(8));
                        $hashedPassword = $passwordHasher->hashPassword($newUser, $randomPassword);
                        $newUser->setPassword($hashedPassword);
                        
                        // Persister le nouvel utilisateur
                        $entityManager->persist($newUser);
                        
                        // Associer le devis au nouvel utilisateur
                        $quote->setUser($newUser);
                        
                        // Envoyer un email avec les informations de connexion
                        try {
                            $accountEmail = (new Email())
                                ->from('noreply@duoimport.mg')
                                ->to($quote->getEmail())
                                ->subject('Votre compte Duo Import MDG a été créé')
                                ->html($this->renderView(
                                    'emails/account_created.html.twig',
                                    [
                                        'user' => $newUser,
                                        'password' => $randomPassword
                                    ]
                                ));
                            
                            $mailer->send($accountEmail);
                        } catch (\Exception $e) {
                            // Log l'erreur mais ne pas l'afficher à l'utilisateur
                            error_log('Erreur lors de l\'envoi de l\'email de création de compte: ' . $e->getMessage());
                        }
                    }
                }
                
                // Sauvegarde dans la base de données
                $entityManager->persist($quote);
                $entityManager->flush();
                
                try {
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
                } catch (\Exception $e) {
                    // Log l'erreur mais ne pas l'afficher à l'utilisateur
                    error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
                }
                
                $this->addFlash('success', 'Votre demande de devis a été envoyée avec succès !');
                
                // Rediriger vers la liste des devis si l'utilisateur est connecté
                if ($user) {
                    return $this->redirectToRoute('app_user_quotes');
                }
                
                return $this->redirectToRoute('app_quote');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de votre demande. Veuillez réessayer ultérieurement.');
                error_log('Erreur lors de l\'enregistrement du devis: ' . $e->getMessage());
            }
        }
        
        return $this->render('quote/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/quote/dashboard', name: 'app_quote_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupération des devis non traités
        $quoteRepository = $entityManager->getRepository(Quote::class);
        $pendingQuotes = $quoteRepository->findBy(['status' => 'pending'], ['createdAt' => 'DESC']);
        $inProgressQuotes = $quoteRepository->findBy(['status' => 'in_progress'], ['createdAt' => 'DESC']);
        $completedQuotes = $quoteRepository->findBy(['status' => 'completed'], ['createdAt' => 'DESC']);
        $rejectedQuotes = $quoteRepository->findBy(['status' => 'rejected'], ['createdAt' => 'DESC']);
        
        return $this->render('quote/dashboard.html.twig', [
            'pendingQuotes' => $pendingQuotes,
            'inProgressQuotes' => $inProgressQuotes,
            'completedQuotes' => $completedQuotes,
            'rejectedQuotes' => $rejectedQuotes,
        ]);
    }

    #[Route('/quote/{id}/status/{status}', name: 'app_quote_status')]
    public function updateStatus(Quote $quote, string $status, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que le statut est valide
        $validStatuses = ['pending', 'in_progress', 'completed', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            throw $this->createNotFoundException('Statut invalide');
        }
        
        // Mettre à jour le statut
        $quote->setStatus($status);
        
        // Si le statut est "completed", marquer comme traité
        if ($status === 'completed') {
            $quote->setProcessed(true);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Le statut du devis a été mis à jour.');
        return $this->redirectToRoute('app_quote_dashboard');
    }

    #[Route('/quote/{id}/view', name: 'app_quote_view')]
    public function viewQuote(Quote $quote): Response
    {
        // Vérifier que l'utilisateur a le droit de voir ce devis
        $user = $this->getUser();
        
        // Si l'utilisateur n'est pas admin et n'est pas le propriétaire du devis
        if (!$this->isGranted('ROLE_ADMIN') && (!$user || $quote->getUser() !== $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }
        
        return $this->render('quote/view.html.twig', [
            'quote' => $quote,
        ]);
    }
} 