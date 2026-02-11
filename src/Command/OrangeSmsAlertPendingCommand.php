<?php

namespace App\Command;

use App\Service\PendingQuoteAlertService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sms:alert-pending',
    description: 'Envoie une alerte SMS et email (devis non traités depuis plus de 2 jours). À lancer manuellement ou en cron quotidien.',
)]
class OrangeSmsAlertPendingCommand extends Command
{
    public function __construct(
        private PendingQuoteAlertService $pendingQuoteAlertService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre de jours sans traitement pour considérer un devis en retard', 2)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Envoyer l\'alerte même si le nombre de devis en attente est 0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) ($input->getOption('days') ?? 2);
        $force = $input->getOption('force');

        $result = $this->pendingQuoteAlertService->run($days, $force);

        if ($result['sms_sent'] === 0 && !$result['email_sent'] && $result['count'] === 0) {
            $io->success($result['message']);
            return Command::SUCCESS;
        }

        if ($result['sms_failures'] !== []) {
            $io->error('Échec SMS : ' . implode(' ; ', array_map(fn ($f) => $f['recipient'] . ' (' . $f['error'] . ')', $result['sms_failures'])));
            return Command::FAILURE;
        }

        if ($result['sms_sent'] > 0) {
            $io->success(sprintf('Alerte SMS envoyée vers %d numéro(s) : %s', $result['sms_sent'], $result['message']));
        }
        if ($result['email_sent']) {
            $io->success('Alerte email envoyée.');
        }
        if ($result['email_error'] !== null) {
            $io->warning('Échec envoi alerte email : ' . $result['email_error']);
        }

        return Command::SUCCESS;
    }
}
