<?php

namespace App\Command;

use App\Service\OrangeSmsSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sms:send-test',
    description: 'Envoie un SMS test via l\'API Orange (Africa & Middle East). Configurez ORANGE_SMS_* dans .env et achetez un bundle sur https://developer.orange.com',
)]
class OrangeSmsSendTestCommand extends Command
{
    private const TEST_MESSAGE = 'Test Duosite – SMS Orange';

    public function __construct(
        private OrangeSmsSender $smsSender,
        private string $testRecipient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'recipient',
            InputArgument::OPTIONAL,
            'Numéro de destination (format international sans +, ex. 261331234567). Si absent, utilise ORANGE_SMS_TEST_RECIPIENT.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient') ?: $this->testRecipient;
        $recipient = trim((string) $recipient);

        if ($recipient === '') {
            $io->error('Aucun destinataire. Définissez ORANGE_SMS_TEST_RECIPIENT dans .env ou passez l\'argument recipient.');
            return Command::FAILURE;
        }

        try {
            $this->smsSender->send($recipient, self::TEST_MESSAGE);
            $io->success(sprintf('SMS test envoyé vers %s.', $recipient));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Échec de l\'envoi : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
