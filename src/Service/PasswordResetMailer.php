<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PasswordResetMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendOtpCode(User $user, string $code): void
    {
        $html = $this->twig->render('emails/password_reset_otp.html.twig', [
            'user' => $user,
            'code' => $code,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nVoici votre code de réinitialisation de mot de passe : %s\n\nCe code expire dans 15 minutes.\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.",
            $user->getFullName(),
            $code
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }
}
