<?php

namespace App\Service;

use App\Entity\QuoteOffer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class PdfGenerator
{
    private $twig;
    private $kernel;
    private $publicDir;

    public function __construct(
        Environment $twig,
        KernelInterface $kernel
    ) {
        $this->twig = $twig;
        $this->kernel = $kernel;
        $this->publicDir = $kernel->getProjectDir() . '/public';
    }

    public function generateQuoteOfferPdf(QuoteOffer $offer): string
    {
        // Définir les options de DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        
        // Instancier DOMPDF
        $dompdf = new Dompdf($options);
        
        // Taille du papier et orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Générer le contenu HTML depuis un template Twig
        $html = $this->twig->render('pdf/quote_offer.html.twig', [
            'offer' => $offer,
        ]);
        
        // Charger le HTML dans DOMPDF
        $dompdf->loadHtml($html);
        
        // Générer le PDF
        $dompdf->render();
        
        // Créer le répertoire si nécessaire
        $pdfDirectory = $this->publicDir . '/uploads/quote_offers/pdf';
        if (!is_dir($pdfDirectory) && !mkdir($pdfDirectory, 0755, true) && !is_dir($pdfDirectory)) {
            throw new \RuntimeException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $pdfDirectory));
        }
        
        // Générer un nom de fichier unique
        $filename = 'offer-' . $offer->getQuote()->getQuoteNumber() . '-' . $offer->getId() . '.pdf';
        $filePath = '/uploads/quote_offers/pdf/' . $filename;
        $fullPath = $this->publicDir . $filePath;
        
        // Enregistrer le PDF dans un fichier
        file_put_contents($fullPath, $dompdf->output());
        
        // Retourner le chemin relatif du fichier
        return $filePath;
    }
} 