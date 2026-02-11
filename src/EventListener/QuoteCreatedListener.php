<?php

namespace App\EventListener;

use App\Event\QuoteCreatedEvent;
use App\Service\OrangeSmsSender;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envoie une alerte SMS et email aux destinataires configurés lorsqu'un nouveau devis est reçu.
 */
class QuoteCreatedListener
{
    public function __construct(
        private OrangeSmsSender $smsSender,
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger,
        private array $alertRecipients,
        private string $alertEmailTo,
        private string $alertEmailCc,
        private string $dashboardUrl
    ) {
    }

    public function onQuoteCreated(QuoteCreatedEvent $event): void
    {
        $quote = $event->getQuote();
        $message = sprintf(
            'Duosite: Nouveau devis #%s reçu.',
            $quote->getQuoteNumber() ?? (string) $quote->getId()
        );

        $recipients = array_filter(array_map('trim', $this->alertRecipients));
        foreach ($recipients as $recipient) {
            if ($recipient === '') {
                continue;
            }
            try {
                $this->smsSender->send($recipient, $message);
                $this->logger->info('Alerte SMS nouveau devis envoyée', [
                    'quote_id' => $quote->getId(),
                    'recipient' => $recipient,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Échec envoi alerte SMS nouveau devis', [
                    'quote_id' => $quote->getId(),
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->alertEmailTo !== '') {
            try {
                $subject = sprintf('Duosite: Nouveau devis #%s reçu.', $quote->getQuoteNumber() ?? $quote->getId());
                $html = $this->twig->render('emails/alert_new_quote.html.twig', [
                    'quote' => $quote,
                    'dashboard_url' => $this->dashboardUrl,
                ]);
                $email = (new Email())
                    ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Alertes'))
                    ->to(trim($this->alertEmailTo))
                    ->subject($subject)
                    ->html($html);
                if (trim($this->alertEmailCc) !== '') {
                    $email->cc(trim($this->alertEmailCc));
                }
                $this->mailer->send($email);
                $this->logger->info('Alerte email nouveau devis envoyée', ['quote_id' => $quote->getId()]);
            } catch (\Throwable $e) {
                $this->logger->error('Échec envoi alerte email nouveau devis', [
                    'quote_id' => $quote->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
