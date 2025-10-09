<?php

namespace App\EventListener;

use App\Event\QuoteStatusChangedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class QuoteStatusChangeListener
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger
    ) {}

    /**
     * Écoute l'événement de changement de statut et envoie les notifications appropriées
     */
    public function onQuoteStatusChanged(QuoteStatusChangedEvent $event): void
    {
        $this->logger->info('Quote status changed', [
            'quote_id' => $event->getQuote()->getId(),
            'quote_number' => $event->getQuote()->getQuoteNumber(),
            'old_status' => $event->getOldStatus(),
            'new_status' => $event->getNewStatus(),
            'changed_by' => $event->getChangedBy()
        ]);

        // Envoyer notification client si nécessaire
        if ($event->requiresClientNotification()) {
            $this->sendClientNotification($event);
        }

        // Envoyer notification admin si nécessaire
        if ($event->requiresAdminNotification()) {
            $this->sendAdminNotification($event);
        }
    }

    /**
     * Envoie une notification par email au client
     */
    private function sendClientNotification(QuoteStatusChangedEvent $event): void
    {
        try {
            $quote = $event->getQuote();
            
            $email = (new Email())
                ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
                ->to($quote->getEmail())
                ->subject($event->getEmailSubject())
                ->html($this->twig->render('emails/quote_status_change.html.twig', [
                    'quote' => $quote,
                    'event' => $event,
                    'notification_type' => $event->getNotificationType(),
                    'notification_message' => $event->getNotificationMessage()
                ]));

            $this->mailer->send($email);

            $this->logger->info('Client notification sent', [
                'quote_id' => $quote->getId(),
                'email' => $quote->getEmail(),
                'status' => $event->getNewStatus()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send client notification', [
                'quote_id' => $event->getQuote()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoie une notification par email à l'administrateur
     */
    private function sendAdminNotification(QuoteStatusChangedEvent $event): void
    {
        try {
            $quote = $event->getQuote();
            
            $email = (new Email())
                ->from(new Address('noreply@duoimport.mg', 'Duo Import MDG - Système'))
                ->to('commercial@duoimport.mg')
                ->subject('Notification Admin - ' . $event->getEmailSubject())
                ->html($this->twig->render('emails/admin_quote_status_change.html.twig', [
                    'quote' => $quote,
                    'event' => $event,
                    'notification_type' => $event->getNotificationType()
                ]));

            $this->mailer->send($email);

            $this->logger->info('Admin notification sent', [
                'quote_id' => $quote->getId(),
                'status' => $event->getNewStatus(),
                'changed_by' => $event->getChangedBy()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send admin notification', [
                'quote_id' => $event->getQuote()->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
