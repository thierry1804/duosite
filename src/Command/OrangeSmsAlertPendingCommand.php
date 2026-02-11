<?php

namespace App\Command;

use App\Repository\QuoteRepository;
use App\Service\OrangeSmsSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:sms:alert-pending',
    description: 'Envoie une alerte SMS et email (devis non traités depuis plus de 2 jours). À lancer manuellement ou en cron quotidien.',
)]
class OrangeSmsAlertPendingCommand extends Command
{
    private const PENDING_DAYS = 2;

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
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre de jours sans traitement pour considérer un devis en retard', self::PENDING_DAYS)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Envoyer l\'alerte même si le nombre de devis en attente est 0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $recipients = array_filter(array_map('trim', $this->alertRecipients));
        if ($recipients === []) {
            $io->error('ORANGE_SMS_ALERT_RECIPIENTS n\'est pas configuré.');
            return Command::FAILURE;
        }

        $days = (int) ($input->getOption('days') ?? self::PENDING_DAYS);
        $count = $this->quoteRepository->countPendingOlderThanDays($days);
        $force = $input->getOption('force');

        if ($count === 0 && !$force) {
            $io->success('Aucun devis en attente depuis plus de ' . $days . ' jour(s). Pas d\'envoi.');
            return Command::SUCCESS;
        }

        $message = $count === 0
            ? sprintf('Duosite: Aucun devis en attente depuis plus de %d jour(s).', $days)
            : sprintf('Duosite: %d devis en attente depuis plus de %d jour(s).', $count, $days);

        $failed = [];
        foreach ($recipients as $recipient) {
            try {
                $this->smsSender->send($recipient, $message);
                $io->success('Alerte SMS envoyée vers ' . $recipient . ' : ' . $message);
            } catch (\Throwable $e) {
                $failed[] = $recipient . ' (' . $e->getMessage() . ')';
            }
        }
        if ($failed !== []) {
            $io->error('Échec pour : ' . implode(' ; ', $failed));
            return Command::FAILURE;
        }

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
                $io->success('Alerte email envoyée vers ' . trim($this->alertEmailTo));
            } catch (\Throwable $e) {
                $io->warning('Échec envoi alerte email : ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
