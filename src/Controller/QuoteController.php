<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteSettings;
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
use Symfony\Component\Mime\Address;
use App\Service\QuoteFeeCalculator;
use App\Service\UserIdentityTracker;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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
        SluggerInterface $slugger,
        QuoteFeeCalculator $feeCalculator,
        UserIdentityTracker $identityTracker
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
                        // Vérifier si un utilisateur avec cet email ou ce téléphone existe déjà
                        $existingUser = $userRepository->findByEmailOrPhone($quote->getEmail(), $quote->getPhone());
                        
                        if ($existingUser) {
                            // Associer le devis à l'utilisateur existant sans mettre à jour ses informations
                            $quote->setUser($existingUser);
                            
                            // Tracer les informations originales de l'utilisateur
                            $identityTracker->traceUserIdentity($quote);
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
                            
                            // Tracer les informations originales de l'utilisateur
                            $identityTracker->traceUserIdentity($quote);
                            
                            // Envoyer un email avec les informations de connexion
                            try {
                                $accountEmail = (new Email())
                                    ->from(new Address('noreply@duoimport.mg', 'Duo Import MDG'))
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
                    
                    // Calcul des frais de devis
                    $feeDetails = $feeCalculator->calculateFee($quote);
                    
                    // Création de l'email pour l'administrateur
                    $emailAdmin = (new Email())
                        ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Système de gestion des devis'))
                        ->replyTo(new Address($quote->getEmail(), $quote->getFirstName() . ' ' . $quote->getLastName()))
                        ->to('commercial@duoimport.mg')
                        ->subject('Nouvelle demande de devis - ' . $quote->getQuoteNumber())
                        ->html($this->renderView(
                            'emails/quote.html.twig',
                            [
                                'quote' => $quote,
                                'feeDetails' => $feeDetails
                            ]
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
                        ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Système de gestion des devis'))
                        ->to($quote->getEmail())
                        ->subject('Confirmation de votre demande de devis - ' . $quote->getQuoteNumber())
                        ->html($this->renderView(
                            'emails/quote_confirmation.html.twig',
                            [
                                'quote' => $quote,
                                'feeDetails' => $feeDetails
                            ]
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
    public function dashboard(EntityManagerInterface $entityManager, QuoteFeeCalculator $feeCalculator): Response
    {
        // Vérifier que l'utilisateur a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupération des devis non traités
        $quoteRepository = $entityManager->getRepository(Quote::class);
        $pendingQuotes = $quoteRepository->findBy(['status' => 'pending'], ['createdAt' => 'DESC']);
        $inProgressQuotes = $quoteRepository->findBy(['status' => 'in_progress'], ['createdAt' => 'DESC']);
        $completedQuotes = $quoteRepository->findBy(['status' => 'completed'], ['createdAt' => 'DESC']);
        $rejectedQuotes = $quoteRepository->findBy(['status' => 'rejected'], ['createdAt' => 'DESC']);

        // Récupérer freeItemsLimit
        $quoteSettings = $entityManager->getRepository(QuoteSettings::class)->findOneBy([]);
        $freeItemsLimit = $quoteSettings->getFreeItemsLimit();
        
        // Recalculer les frais pour tous les devis pour s'assurer que le statut de paiement est à jour
        foreach (array_merge($pendingQuotes, $inProgressQuotes, $completedQuotes, $rejectedQuotes) as $quote) {
            $feeCalculator->calculateFee($quote);
        }
        
        // Sauvegarder les mises à jour potentielles du statut de paiement
        $entityManager->flush();
        
        return $this->render('quote/dashboard.html.twig', [
            'pendingQuotes' => $pendingQuotes,
            'inProgressQuotes' => $inProgressQuotes,
            'completedQuotes' => $completedQuotes,
            'rejectedQuotes' => $rejectedQuotes,
            'processedQuotes' => array_merge($completedQuotes, $rejectedQuotes),
            'freeItemsLimit' => $freeItemsLimit,
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
    public function viewQuote(Quote $quote, QuoteFeeCalculator $feeCalculator, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur a le droit de voir ce devis
        $user = $this->getUser();
        
        // Si l'utilisateur n'est pas admin et n'est pas le propriétaire du devis
        if (!$this->isGranted('ROLE_ADMIN') && (!$user || $quote->getUser() !== $user)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette demande de devis.');
        }
        
        // Calculer les frais de devis pour avoir les informations détaillées
        $feeDetails = $feeCalculator->calculateFee($quote);
        
        // Sauvegarder les changements potentiels de statut de paiement
        $entityManager->flush();
        
        return $this->render('quote/view.html.twig', [
            'quote' => $quote,
            'feeDetails' => $feeDetails
        ]);
    }

    #[Route('/quote/{id}/process', name: 'app_quote_process')]
    public function processQuote(Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier si un paiement est requis
        if ($quote->isPaymentRequired()) {
            // Si le paiement est requis mais pas encore effectué
            if (!$quote->isPaid()) {
                $this->addFlash('error', 'Ce devis nécessite un paiement avant de pouvoir être traité. Veuillez confirmer le paiement d\'abord.');
                return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
            }
        }
        
        // Mettre à jour le statut du devis à "in_progress"
        $quote->setStatus('in_progress');
        $entityManager->flush();
        
        $this->addFlash('success', 'Le devis a été marqué comme "En cours de traitement".');
        
        // Rediriger vers la page de détail du devis
        return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
    }

    #[Route('/quote/{id}/mark-as-paid', name: 'app_quote_mark_as_paid')]
    public function markAsPaid(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur a le rôle ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier si le paiement est requis
        if (!$quote->isPaymentRequired()) {
            $this->addFlash('warning', 'Ce devis ne nécessite pas de paiement.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Vérifier si le devis est déjà payé
        if ($quote->isPaid()) {
            $this->addFlash('info', 'Ce devis a déjà été marqué comme payé.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Créer un formulaire pour les informations de paiement
        $form = $this->createFormBuilder($quote)
            ->add('transactionReference', TextType::class, [
                'label' => 'Référence de transaction',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('paymentDate', DateTimeType::class, [
                'label' => 'Date de paiement',
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'data' => new \DateTime()
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Confirmer le paiement',
                'attr' => ['class' => 'btn btn-success']
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Marquer le devis comme payé
            $quote->setPaymentStatus('completed');
            $entityManager->flush();
            
            $this->addFlash('success', 'Le paiement du devis a été confirmé. Vous pouvez maintenant le traiter.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        return $this->render('quote/mark_as_paid.html.twig', [
            'quote' => $quote,
            'form' => $form->createView()
        ]);
    }
} 