<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AdminAccountMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendInvitation(User $admin, string $activationUrl): void
    {
        $html = $this->twig->render('emails/admin_invitation.html.twig', [
            'admin' => $admin,
            'activationUrl' => $activationUrl,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nUn compte administrateur a ete cree pour vous sur Duo Import MDG.\nConfirmez votre acces via ce lien : %s\n\nCe lien a une duree de validite limitee.",
            $admin->getFullName(),
            $activationUrl
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $admin->getEmail())
            ->subject('Confirmation de votre compte administrateur')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }

    public function sendOtpCode(User $admin, string $code): void
    {
        $html = $this->twig->render('emails/admin_activation_code.html.twig', [
            'admin' => $admin,
            'code' => $code,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nVoici votre code de validation administrateur : %s\n\nCe code expire dans 15 minutes.",
            $admin->getFullName(),
            $code
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $admin->getEmail())
            ->subject('Code de validation administrateur')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }

    public function sendAccountUpdated(User $admin): void
    {
        $html = $this->twig->render('emails/admin_account_updated.html.twig', [
            'admin' => $admin,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nVotre compte administrateur Duo Import MDG a ete modifie.\nSi vous n'etes pas a l'origine de ce changement, contactez immediatement un administrateur principal.",
            $admin->getFullName()
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $admin->getEmail())
            ->subject('Votre compte administrateur a été modifié')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }

    public function sendAccountDeleted(User $admin): void
    {
        $html = $this->twig->render('emails/admin_account_deleted.html.twig', [
            'admin' => $admin,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nVotre compte administrateur Duo Import MDG a ete supprime.\nSi cette action vous parait anormale, merci de contacter l'equipe responsable.",
            $admin->getFullName()
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $admin->getEmail())
            ->subject('Votre compte administrateur a été supprimé')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }
}
