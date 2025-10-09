<?php

namespace App\Command;

use App\Entity\Quote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
// use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:generate-tracking-tokens',
    description: 'Génère les tracking tokens pour tous les devis existants',
)]
class GenerateTrackingTokensCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération des tracking tokens');

        // Récupérer tous les devis sans tracking token
        $quotes = $this->entityManager->getRepository(Quote::class)->findBy([
            'trackingToken' => null
        ]);

        if (empty($quotes)) {
            $io->success('Tous les devis ont déjà un tracking token.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Génération des tracking tokens pour %d devis...', count($quotes)));

        $progressBar = $io->createProgressBar(count($quotes));
        $progressBar->start();

        $generated = 0;
        foreach ($quotes as $quote) {
            // Générer un UUID unique (format UUID v4)
            $trackingToken = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $quote->setTrackingToken($trackingToken);
            
            $generated++;
            $progressBar->advance();
        }

        // Sauvegarder en base
        $this->entityManager->flush();

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Génération terminée ! %d tracking tokens générés.', $generated));

        return Command::SUCCESS;
    }
}
