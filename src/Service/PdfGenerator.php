<?php

namespace App\Service;

ini_set('memory_limit', '512M');
set_time_limit(120);

use App\Entity\ProductProposal;
use App\Entity\QuoteOffer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class PdfGenerator
{
    private Environment $twig;
    private string $publicDir;

    public function __construct(
        Environment $twig,
        KernelInterface $kernel
    ) {
        $this->twig = $twig;
        $this->publicDir = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public';
    }

    public function generateQuoteOfferPdf(QuoteOffer $offer): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('isPhpEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('dpi', 96);
        $options->set('defaultPaperSize', 'A4');
        $options->setChroot($this->publicDir);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setBasePath($this->publicDir);

        $html = $this->twig->render('pdf/quote_offer.html.twig', [
            'offer' => $offer,
            'pdf_logo' => $this->resolvePublicRelativePath('images/logo.webp'),
            'catalog_images' => $this->buildCatalogImages($offer),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        $pdfDirectory = $this->publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'quote_offers' . DIRECTORY_SEPARATOR . 'pdf';
        if (!is_dir($pdfDirectory) && !mkdir($pdfDirectory, 0755, true) && !is_dir($pdfDirectory)) {
            throw new \RuntimeException(sprintf('Le répertoire "%s" n\'a pas pu être créé', $pdfDirectory));
        }

        $filename = 'offer-' . $offer->getQuote()->getQuoteNumber() . '-' . $offer->getId() . '.pdf';
        $filePath = '/uploads/quote_offers/pdf/' . $filename;
        $fullPath = $this->publicDir . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

        file_put_contents($fullPath, $dompdf->output());

        return $filePath;
    }

    /**
     * @return list<list<string>> Chemins relatifs public/ par proposition (index = ordre d'offre)
     */
    private function buildCatalogImages(QuoteOffer $offer): array
    {
        $result = [];
        foreach ($offer->getProductProposals() as $proposal) {
            $result[] = $this->resolveProposalImagePaths($proposal);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function resolveProposalImagePaths(ProductProposal $proposal): array
    {
        $names = $proposal->getImages();
        if ($names === [] && $proposal->getQuoteItem()?->getPhotoFilename()) {
            $names = [$proposal->getQuoteItem()->getPhotoFilename()];
        }

        $paths = [];
        foreach ($names as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $src = $this->resolvePublicRelativePath(
                'uploads/product_proposals/' . $name,
                'uploads/quote_photos/' . $name
            );
            if ($src !== null) {
                $paths[] = $src;
            }
        }

        return $paths;
    }

    /**
     * Chemin relatif à public/ pour Dompdf (chroot), ou null.
     */
    public function resolvePublicRelativePath(string ...$relativeCandidates): ?string
    {
        foreach ($relativeCandidates as $candidate) {
            $rel = str_replace('\\', '/', ltrim($candidate, '/'));
            $full = $this->publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($full)) {
                return $rel;
            }
        }

        return null;
    }
}
