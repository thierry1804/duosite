<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\User;
use App\Entity\QuoteItem;
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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\Transport\TransportInterface;

class QuoteController extends AbstractController
{
    #[Route('/quote', name: 'app_quote', methods: ['GET', 'POST'])]
    public function index(
        Request $request, 
        MailerInterface $mailer,
        TransportInterface $transport,
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
        
        // Ajouter un item par défaut
        $item = new QuoteItem();
        $quote->addItem($item);
        
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérifier que chaque item a les champs obligatoires remplis
            $itemsValid = true;
            foreach ($quote->getItems() as $item) {
                if (empty($item->getProductType()) || empty($item->getDescription()) || empty($item->getQuantity())) {
                    $itemsValid = false;
                    break;
                }
                
                // Vérifier si le type est "Autre" et que le champ otherProductType est rempli
                if ($item->getProductType() === 'Autre' && empty($item->getOtherProductType())) {
                    $itemsValid = false;
                    break;
                }
            }
            
            if ($form->isValid() && $itemsValid) {
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
                    
                    // Traiter les photos pour chaque item
                    $filesData = $request->files->get('quote');
                    error_log('Files data: ' . print_r($filesData, true));
                    
                    if (isset($filesData['items']) && is_array($filesData['items'])) {
                        // Créer un tableau associatif des fichiers par index
                        $photoFiles = [];
                        foreach ($filesData['items'] as $fileIndex => $fileData) {
                            error_log("Checking file data for index $fileIndex: " . print_r($fileData, true));
                            if (isset($fileData['photoFile']) && $fileData['photoFile']) {
                                $photoFiles[$fileIndex] = $fileData['photoFile'];
                                error_log("Added photo file for index $fileIndex");
                            }
                        }
                        
                        error_log('Photo files: ' . print_r(array_keys($photoFiles), true));
                        
                        // Parcourir les items du formulaire et associer les photos
                        foreach ($form->get('items') as $index => $itemForm) {
                            error_log("Processing item form at index $index");
                            if (isset($photoFiles[$index])) {
                                $photoFile = $photoFiles[$index];
                                $item = $itemForm->getData();
                                error_log("Found photo file for item at index $index");
                                
                                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                                // Sécuriser le nom du fichier
                                $safeFilename = $slugger->slug($originalFilename);
                                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();
                                
                                try {
                                    // Déplacer le fichier dans le répertoire final
                                    $photoFile->move(
                                        $this->getParameter('quote_photos_directory'),
                                        $newFilename
                                    );
                                    
                                    // Mettre à jour le nom du fichier dans l'entité
                                    $item->setPhotoFilename($newFilename);
                                } catch (FileException $e) {
                                    // Log l'erreur mais continuer le traitement
                                    error_log('Erreur lors du téléchargement de la photo: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    
                    // Sauvegarde dans la base de données
                    $entityManager->persist($quote);
                    $entityManager->flush();
                    
                    // Création de l'email pour l'administrateur
                    $emailAdmin = (new Email())
                        ->from('commercial@duoimport.mg')
                        ->replyTo($quote->getEmail())
                        ->to('commercial@duoimport.mg')
                        ->subject('Nouvelle demande de devis - ' . $quote->getQuoteNumber())
                        ->html($this->renderView(
                            'emails/quote.html.twig',
                            ['quote' => $quote]
                        ));
                    
                    // Envoi de l'email à l'administrateur
                    try {
                        // Utiliser directement le transport pour plus de contrôle
                        $message = $transport->send($emailAdmin);
                        error_log('Email administrateur envoyé avec succès via transport direct');
                    } catch (\Exception $e) {
                        error_log('Erreur lors de l\'envoi de l\'email administrateur: ' . $e->getMessage());
                        error_log('Trace: ' . $e->getTraceAsString());
                    }
                    
                    // Création de l'email de confirmation pour le client
                    $emailClient = (new Email())
                        ->from('commercial@duoimport.mg')
                        ->to($quote->getEmail())
                        ->subject('Confirmation de votre demande de devis - ' . $quote->getQuoteNumber())
                        ->html($this->renderView(
                            'emails/quote_confirmation.html.twig',
                            ['quote' => $quote]
                        ));
                    
                    // Envoi de l'email au client
                    try {
                        // Utiliser directement le transport pour plus de contrôle
                        $message = $transport->send($emailClient);
                        error_log('Email client envoyé avec succès à ' . $quote->getEmail() . ' via transport direct');
                    } catch (\Exception $e) {
                        error_log('Erreur lors de l\'envoi de l\'email client: ' . $e->getMessage());
                        error_log('Trace: ' . $e->getTraceAsString());
                    }
                    
                    $this->addFlash('success', 'Votre demande de devis a été envoyée avec succès !');
                    
                    // Rediriger vers la liste des devis si l'utilisateur est connecté
                    if ($user) {
                        return $this->redirectToRoute('app_user_quotes');
                    }
                    
                    return $this->redirectToRoute('app_quote');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement de votre demande. Veuillez réessayer ultérieurement.' . $e->getMessage());
                    error_log('Erreur lors de l\'enregistrement du devis: ' . $e->getMessage());
                    error_log('Trace: ' . $e->getTraceAsString());
                }
            } else {
                if (!$itemsValid) {
                    $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires pour chaque produit (type, description et quantité).');
                }
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