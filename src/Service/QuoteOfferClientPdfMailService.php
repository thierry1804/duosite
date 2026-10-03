<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\QuoteOffer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envoie au client un email avec le PDF de l'offre (envoi, renvoi, acceptation).
 * Une copie est toujours adressée à contact@duoimport.mg.
 */
class QuoteOfferClientPdfMailService
{
    public const CONTACT_COPY_EMAIL = 'contact@duoimport.mg';

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private PdfGenerator $pdfGenerator,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Envoie l'offre PDF au client (template devis) + copie à contact@duoimport.mg.
     * Génère le PDF s'il est manquant.
     */
    public function sendOfferPdfToClient(Quote $quote, QuoteOffer $offer): void
    {
        $pdfAbsolutePath = $this->ensureOfferPdfAbsolutePath($offer);

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $quote->getEmail())
            ->bcc(self::CONTACT_COPY_EMAIL)
            ->subject('Votre devis #' . $quote->getQuoteNumber())
            ->html($this->twig->render('emails/quote_offer.html.twig', [
                'quote' => $quote,
                'offer' => $offer,
            ]))
            ->attachFromPath($pdfAbsolutePath, 'devis.pdf', 'application/pdf');

        $this->mailer->send($email);

        $this->logger->info('Email offre PDF envoyé au client (copie contact)', [
            'quote_id' => $quote->getId(),
            'offer_id' => $offer->getId(),
            'to' => $quote->getEmail(),
            'bcc' => self::CONTACT_COPY_EMAIL,
        ]);
    }

    public function sendClientAcceptedOfferEmail(Quote $quote): void
    {
        $offer = $this->findAcceptedOffer($quote);
        if ($offer === null) {
            $this->logger->warning('Aucune offre au statut accepté pour l\'envoi PDF au client', ['quote_id' => $quote->getId()]);

            return;
        }

        try {
            $pdfAbsolutePath = $this->ensureOfferPdfAbsolutePath($offer);
            $email = (new Email())
                ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
                ->to((string) $quote->getEmail())
                ->bcc(self::CONTACT_COPY_EMAIL)
                ->subject(sprintf('Devis #%s - Confirmation de votre acceptation', $quote->getQuoteNumber()))
                ->html($this->twig->render('emails/quote_offer_accepted_client.html.twig', [
                    'quote' => $quote,
                    'offer' => $offer,
                ]))
                ->attachFromPath($pdfAbsolutePath, 'devis.pdf', 'application/pdf');

            $this->mailer->send($email);

            $this->logger->info('Email acceptation offre avec PDF envoyé au client', [
                'quote_id' => $quote->getId(),
                'offer_id' => $offer->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Échec envoi email acceptation offre avec PDF', [
                'quote_id' => $quote->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findAcceptedOffer(Quote $quote): ?QuoteOffer
    {
        $offers = $quote->getOffers()->toArray();
        usort($offers, fn (QuoteOffer $a, QuoteOffer $b) => $b->getId() <=> $a->getId());
        foreach ($offers as $offer) {
            if ($offer->getStatus() === 'accepted') {
                return $offer;
            }
        }

        return null;
    }

    private function ensureOfferPdfAbsolutePath(QuoteOffer $offer): string
    {
        $rel = $offer->getPdfFilePath();
        if ($rel !== null && $rel !== '') {
            $full = $this->projectDir.'/public'.$rel;
            if (is_file($full) && is_readable($full)) {
                return $full;
            }
        }

        $relativePdfPath = $this->pdfGenerator->generateQuoteOfferPdf($offer);
        $offer->setPdfFilePath($relativePdfPath);
        $this->entityManager->flush();

        $pdfAbsolutePath = $this->projectDir.'/public'.$relativePdfPath;
        if (!is_file($pdfAbsolutePath) || !is_readable($pdfAbsolutePath)) {
            throw new \RuntimeException('Le fichier PDF généré est introuvable.');
        }

        return $pdfAbsolutePath;
    }
}
