<?php

namespace App\Controller;

use App\Entity\Quote;
use App\Entity\QuoteOffer;
use App\Entity\ProductProposal;
use App\Entity\ShippingOption;
use App\Form\QuoteOfferType;
use App\Repository\QuoteOfferRepository;
use App\Repository\ProductProposalRepository;
use App\Repository\ShippingOptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\ExchangeRateService;
use App\Service\PdfGenerator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

#[Route('/admin/quote-offer')]
class QuoteOfferController extends AbstractController
{
    #[Route('/quote/{id}/create-offer', name: 'app_quote_offer_create', methods: ['GET', 'POST'])]
    public function createOffer(
        Request $request,
        Quote $quote,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ExchangeRateService $exchangeRateService
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que le devis est en cours de traitement
        if ($quote->getStatus() !== 'in_progress') {
            $this->addFlash('error', 'Vous ne pouvez créer une offre que pour un devis en cours de traitement.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Créer une nouvelle offre
        $offer = new QuoteOffer();
        $offer->setQuote($quote);
        $offer->setTitle('Offre ' . date('d/m/Y'));
        
        // Obtenir les éléments du devis à proposer
        $quoteItems = $quote->getItems();
        
        // Récupérer le taux de change RMB du jour (ne pas l'enregistrer directement, le laisser au formulaire)
        $rmbRate = $exchangeRateService->getRmbMgaRate();
        
        // Ajouter automatiquement les options d'expédition basées sur les choix d'envoi du client
        if (!empty($quote->getShippingMethod())) {
            $shippingMethods = $quote->getShippingMethod();
            $shippingNames = [
                'maritime' => 'Maritime',
                'aerien_express' => 'Aérien Express',
                'aerien_normal' => 'Aérien Standard'
            ];
            $deliveryDays = [
                'maritime' => 45,
                'aerien_express' => 7,
                'aerien_normal' => 15
            ];
            
            foreach ($shippingMethods as $methodCode) {
                if (isset($shippingNames[$methodCode])) {
                    $shippingOption = new ShippingOption();
                    $shippingOption->setName($shippingNames[$methodCode]);
                    $shippingOption->setDescription('Option d\'expédition choisie par le client');
                    $shippingOption->setPrice(0); // Prix à définir par l'administrateur
                    
                    if (isset($deliveryDays[$methodCode])) {
                        $shippingOption->setEstimatedDeliveryDays($deliveryDays[$methodCode]);
                    }
                    
                    $offer->addShippingOption($shippingOption);
                }
            }
        }
        
        // Créer le formulaire
        $form = $this->createForm(QuoteOfferType::class, $offer, [
            'quote_items' => $quoteItems->toArray(),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Assurer que le répertoire d'upload existe
            $uploadDir = $this->getParameter('product_proposal_images_directory');
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $uploadDir));
            }
            
            // Traiter chaque proposition de produit
            foreach ($offer->getProductProposals() as $proposal) {
                // Récupérer les fichiers téléchargés directement de l'entité productProposal
                $imageFiles = $proposal->getImageFiles();
                
                if ($imageFiles) {
                    foreach ($imageFiles as $imageFile) {
                        if ($imageFile) {
                            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            // Sécuriser le nom du fichier
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                            
                            try {
                                // Déplacer le fichier dans le répertoire final
                                $imageFile->move(
                                    $this->getParameter('product_proposal_images_directory'),
                                    $newFilename
                                );
                                
                                // Ajouter l'image à la proposition
                                $proposal->addImage($newFilename);
                            } catch (FileException $e) {
                                // Log l'erreur mais continuer le traitement
                                error_log('Erreur lors du téléchargement de l\'image: ' . $e->getMessage());
                                $this->addFlash('error', 'Erreur lors du téléchargement d\'une image: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            // Calculer le prix total de l'offre
            $offer->calculateTotalPrice();
            
            // Persister l'offre
            $entityManager->persist($offer);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été créée avec succès.');
            
            return $this->redirectToRoute('app_quote_offer_edit', ['id' => $offer->getId()]);
        }
        
        return $this->render('quote_offer/create.html.twig', [
            'quote' => $quote,
            'form' => $form->createView(),
            'rmb_rate' => $rmbRate,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_quote_offer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        QuoteOffer $offer,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ExchangeRateService $exchangeRateService
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Obtenir le devis associé
        $quote = $offer->getQuote();
        
        // Vérifier que le devis est en cours de traitement
        if ($quote->getStatus() !== 'in_progress') {
            $this->addFlash('error', 'Vous ne pouvez éditer une offre que pour un devis en cours de traitement.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Obtenir les éléments du devis
        $quoteItems = $quote->getItems();
        
        // Récupérer le taux de change RMB du jour
        $rmbRate = $exchangeRateService->getRmbMgaRate();
        
        // Créer le formulaire
        $form = $this->createForm(QuoteOfferType::class, $offer, [
            'quote_items' => $quoteItems->toArray(),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Assurer que le répertoire d'upload existe
            $uploadDir = $this->getParameter('product_proposal_images_directory');
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $uploadDir));
            }
            
            // Traiter chaque proposition de produit
            foreach ($offer->getProductProposals() as $proposal) {
                // Récupérer les fichiers téléchargés directement de l'entité productProposal
                $imageFiles = $proposal->getImageFiles();
                
                if ($imageFiles) {
                    foreach ($imageFiles as $imageFile) {
                        if ($imageFile) {
                            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                            // Sécuriser le nom du fichier
                            $safeFilename = $slugger->slug($originalFilename);
                            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                            
                            try {
                                // Déplacer le fichier dans le répertoire final
                                $imageFile->move(
                                    $this->getParameter('product_proposal_images_directory'),
                                    $newFilename
                                );
                                
                                // Ajouter l'image à la proposition
                                $proposal->addImage($newFilename);
                            } catch (FileException $e) {
                                // Log l'erreur mais continuer le traitement
                                error_log('Erreur lors du téléchargement de l\'image: ' . $e->getMessage());
                                $this->addFlash('error', 'Erreur lors du téléchargement d\'une image: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            // Calculer le prix total de l'offre
            $offer->calculateTotalPrice();
            
            // Persister les modifications
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été mise à jour avec succès.');
            
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        return $this->render('quote_offer/edit.html.twig', [
            'quote' => $quote,
            'offer' => $offer,
            'form' => $form->createView(),
            'rmb_rate' => $rmbRate,
            'current_rmb_rate' => $offer->getRmbMgaExchangeRate(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_quote_offer_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        QuoteOffer $offer,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Obtenir le devis associé
        $quote = $offer->getQuote();
        
        // Vérifier que le devis est en cours de traitement
        if ($quote->getStatus() !== 'in_progress') {
            $this->addFlash('error', 'Vous ne pouvez supprimer une offre que pour un devis en cours de traitement.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$offer->getId(), $request->request->get('_token'))) {
            // Supprimer l'offre
            $entityManager->remove($offer);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été supprimée avec succès.');
        }
        
        return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
    }
    
    #[Route('/{id}/send', name: 'app_quote_offer_send', methods: ['POST'])]
    public function sendOffer(
        Request $request,
        QuoteOffer $offer,
        EntityManagerInterface $entityManager,
        PdfGenerator $pdfGenerator
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Obtenir le devis associé
        $quote = $offer->getQuote();
        
        // Vérifier que le devis est en cours de traitement
        if ($quote->getStatus() !== 'in_progress') {
            $this->addFlash('error', 'Vous ne pouvez envoyer une offre que pour un devis en cours de traitement.');
            return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
        }
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('send'.$offer->getId(), $request->request->get('_token'))) {
            // Mettre à jour le statut de l'offre
            $offer->setStatus('sent');
            
            // Générer le PDF et l'attacher à l'offre
            try {
                $pdfPath = $pdfGenerator->generateQuoteOfferPdf($offer);
                $offer->setPdfFilePath($pdfPath);
                
                $entityManager->flush();
                
                // TODO: Envoyer un email au client avec l'offre
                
                $this->addFlash('success', 'L\'offre a été envoyée au client avec succès et le PDF a été généré.');
            } catch (\Exception $e) {
                // En cas d'erreur avec la génération du PDF
                $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
                return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
            }
        }
        
        return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
    }
    
    #[Route('/{id}', name: 'app_quote_offer_show', methods: ['GET'])]
    public function show(QuoteOffer $offer, ExchangeRateService $exchangeRateService): Response
    {
        // Récupérer le taux de change RMB du jour
        $rmbRate = $exchangeRateService->getRmbMgaRate();
        
        return $this->render('quote_offer/view.html.twig', [
            'offer' => $offer,
            'rmb_rate' => $rmbRate,
            'saved_rmb_rate' => $offer->getRmbMgaExchangeRate(),
        ]);
    }
    
    #[Route('/api/rmb-mga-rate', name: 'app_exchange_rate_api', methods: ['GET'])]
    public function getRmbMgaRate(ExchangeRateService $exchangeRateService): Response
    {
        $rateInfo = $exchangeRateService->getExchangeRateInfo();
        $rate = $exchangeRateService->getRmbMgaRate();
        
        return $this->json([
            'success' => $rate !== null,
            'rate' => $rate,
            'last_update' => $rateInfo ? $rateInfo['last_update'] : null,
            'next_update' => $rateInfo ? $rateInfo['next_update'] : null,
            'provider' => $rateInfo ? $rateInfo['provider'] : 'exchangerate-api.com',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
    
    #[Route('/{id}/generate-pdf', name: 'app_quote_offer_generate_pdf', methods: ['GET'])]
    public function generatePdf(
        QuoteOffer $offer,
        EntityManagerInterface $entityManager,
        PdfGenerator $pdfGenerator
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        try {
            // Générer le PDF
            $pdfPath = $pdfGenerator->generateQuoteOfferPdf($offer);
            
            // Mettre à jour le chemin du PDF dans l'offre
            $offer->setPdfFilePath($pdfPath);
            $entityManager->flush();
            
            // Lire le contenu du fichier PDF
            $pdfContent = file_get_contents($this->getParameter('kernel.project_dir') . '/public' . $pdfPath);
            
            // Créer la réponse avec le contenu du PDF
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'inline; filename="offre-' . $offer->getId() . '.pdf"');
            
            return $response;
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
            return $this->redirectToRoute('app_quote_offer_edit', ['id' => $offer->getId()]);
        }
    }

    #[Route('/{id}/send-pdf', name: 'app_quote_offer_send_pdf', methods: ['POST'])]
    public function sendPdf(
        Request $request,
        QuoteOffer $offer,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response
    {
        // Vérifier les permissions
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Obtenir le devis associé
        $quote = $offer->getQuote();
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('send_pdf'.$offer->getId(), $request->request->get('_token'))) {
            try {
                // Créer l'email
                $email = (new Email())
                    ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
                    ->to($quote->getEmail())
                    ->subject('Votre devis #' . $quote->getQuoteNumber())
                    ->html($this->renderView(
                        'emails/quote_offer.html.twig',
                        [
                            'quote' => $quote,
                            'offer' => $offer
                        ]
                    ));

                // Ajouter le PDF en pièce jointe
                if ($offer->getPdfFilePath()) {
                    $pdfPath = $this->getParameter('kernel.project_dir') . '/public' . $offer->getPdfFilePath();
                    if (file_exists($pdfPath)) {
                        $email->attachFromPath($pdfPath, 'devis.pdf', 'application/pdf');
                    }
                }

                // Envoyer l'email
                $mailer->send($email);

                // Mettre à jour le statut de l'offre
                $offer->setStatus('sent');
                $entityManager->flush();

                $this->addFlash('success', 'Le devis a été envoyé par email avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_quote_offer_edit', ['id' => $offer->getId()]);
    }
} 