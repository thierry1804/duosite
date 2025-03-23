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

#[Route('/admin/quote-offer')]
class QuoteOfferController extends AbstractController
{
    #[Route('/quote/{id}/create-offer', name: 'app_quote_offer_create', methods: ['GET', 'POST'])]
    public function createOffer(
        Request $request,
        Quote $quote,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
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
        
        // Créer le formulaire
        $form = $this->createForm(QuoteOfferType::class, $offer, [
            'quote_items' => $quoteItems->toArray(),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Traiter chaque proposition de produit
            foreach ($offer->getProductProposals() as $proposal) {
                // Traiter les images téléchargées
                $filesData = $request->files->get('quote_offer');
                if (isset($filesData['productProposals'])) {
                    foreach ($filesData['productProposals'] as $index => $proposalData) {
                        if (isset($proposalData['imageFiles']) && $proposalData['imageFiles']) {
                            $imageFiles = $proposalData['imageFiles'];
                            
                            // Traiter chaque image
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
                                    }
                                }
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
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_quote_offer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        QuoteOffer $offer,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
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
        
        // Créer le formulaire
        $form = $this->createForm(QuoteOfferType::class, $offer, [
            'quote_items' => $quoteItems->toArray(),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Traiter chaque proposition de produit
            foreach ($offer->getProductProposals() as $proposal) {
                // Traiter les images téléchargées
                $filesData = $request->files->get('quote_offer');
                if (isset($filesData['productProposals'])) {
                    foreach ($filesData['productProposals'] as $index => $proposalData) {
                        if (isset($proposalData['imageFiles']) && $proposalData['imageFiles']) {
                            $imageFiles = $proposalData['imageFiles'];
                            
                            // Traiter chaque image
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
                                    }
                                }
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
        EntityManagerInterface $entityManager
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
            $entityManager->flush();
            
            // TODO: Envoyer un email au client avec l'offre
            
            $this->addFlash('success', 'L\'offre a été envoyée au client avec succès.');
        }
        
        return $this->redirectToRoute('app_quote_view', ['id' => $quote->getId()]);
    }
} 