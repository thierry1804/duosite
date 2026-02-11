<?php

namespace App\Service;

use App\Repository\QuoteRepository;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

/**
 * Envoi des alertes (SMS + email) pour les devis en attente depuis X jours.
 * Utilisé par la commande app:sms:alert-pending et la route /quote/notifier.
 */
class PendingQuoteAlertService
{
    private const PENDING_DAYS_DEFAULT = 2;

    public function __construct(
        private QuoteRepository $quoteRepository,
        private OrangeSmsSender $smsSender,
        private MailerInterface $mailer,
        private Environment $twig,
        private array $alertRecipients,
        private string $alertEmailTo,
        private string $alertEmailCc,
        private string $dashboardUrl
    ) {
    }

    /**
     * Envoie les alertes (SMS et email) selon le nombre de devis en attente.
     *
     * @return array{ok: bool, count: int, days: int, message: string, sms_sent: int, sms_failures: array, email_sent: bool, email_error: string|null}
     */
    public function run(int $days = self::PENDING_DAYS_DEFAULT, bool $force = false): array
    {
        $recipients = array_filter(array_map('trim', $this->alertRecipients));
        $count = $this->quoteRepository->countPendingOlderThanDays($days);

        if ($count === 0 && !$force) {
            return [
                'ok' => true,
                'count' => 0,
                'days' => $days,
                'message' => 'Aucun devis en attente depuis plus de ' . $days . ' jour(s). Pas d\'envoi.',
                'sms_sent' => 0,
                'sms_failures' => [],
                'email_sent' => false,
                'email_error' => null,
            ];
        }

        $message = $count === 0
            ? sprintf('Duosite: Aucun devis en attente depuis plus de %d jour(s).', $days)
            : sprintf('Duosite: %d devis en attente depuis plus de %d jour(s).', $count, $days);

        $smsSent = 0;
        $smsFailures = [];
        foreach ($recipients as $recipient) {
            if ($recipient === '') {
                continue;
            }
            try {
                $this->smsSender->send($recipient, $message);
                $smsSent++;
            } catch (\Throwable $e) {
                $smsFailures[] = ['recipient' => $recipient, 'error' => $e->getMessage()];
            }
        }

        $emailSent = false;
        $emailError = null;
        if (trim($this->alertEmailTo) !== '') {
            try {
                $html = $this->twig->render('emails/alert_pending_quotes.html.twig', [
                    'count' => $count,
                    'days' => $days,
                    'dashboard_url' => $this->dashboardUrl,
                ]);
                $email = (new Email())
                    ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG - Alertes'))
                    ->to(trim($this->alertEmailTo))
                    ->subject($message)
                    ->html($html);
                if (trim($this->alertEmailCc) !== '') {
                    $email->cc(trim($this->alertEmailCc));
                }
                $this->mailer->send($email);
                $emailSent = true;
            } catch (\Throwable $e) {
                $emailError = $e->getMessage();
            }
        }

        return [
            'ok' => empty($smsFailures),
            'count' => $count,
            'days' => $days,
            'message' => $message,
            'sms_sent' => $smsSent,
            'sms_failures' => $smsFailures,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
        ];
    }
}
