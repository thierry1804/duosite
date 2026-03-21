<?php

namespace App\EventListener;

use App\Entity\ImportOrderStatusHistory;
use App\Event\ImportOrderStatusChangedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ImportOrderStatusChangeListener
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function onImportOrderStatusChanged(ImportOrderStatusChangedEvent $event): void
    {
        $order = $event->getOrder();
        $recipient = trim((string) $order->getEmail());
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $oldStatusLabel = ImportOrderStatusHistory::getStatusLabel($event->getOldStatus());
        $newStatusLabel = ImportOrderStatusHistory::getStatusLabel($event->getNewStatus());
        $trackingUrl = $this->urlGenerator->generate('app_import_order_tracking_show', ['token' => $order->getTrackingToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to($recipient)
            ->subject(sprintf('Mise à jour de votre commande %s : %s', $order->getOrderNumber(), $newStatusLabel))
            ->html($this->twig->render('emails/import_order_status_changed.html.twig', [
                'order' => $order,
                'oldStatusLabel' => $oldStatusLabel,
                'newStatusLabel' => $newStatusLabel,
                'comment' => $event->getComment(),
                'trackingUrl' => $trackingUrl,
            ]));

        $this->mailer->send($email);
    }
}
