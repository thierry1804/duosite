<?php

namespace App\Command;

use App\Entity\Quote;
use App\Service\QuoteFeeCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-quotes',
    description: 'Recalculate payment status for all quotes'
)]
class RecalculateQuotesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private QuoteFeeCalculator $feeCalculator;
    
    public function __construct(EntityManagerInterface $entityManager, QuoteFeeCalculator $feeCalculator)
    {
        $this->entityManager = $entityManager;
        $this->feeCalculator = $feeCalculator;
        
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $quoteRepository = $this->entityManager->getRepository(Quote::class);
        $quotes = $quoteRepository->findAll();
        
        $io->progressStart(count($quotes));
        
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($quotes as $quote) {
            try {
                // Calculer les frais et mettre à jour le statut de paiement
                $feeDetails = $this->feeCalculator->calculateFee($quote);
                $updated++;
            } catch (\Throwable $e) {
                // En cas d'erreur, logger et continuer avec le prochain devis
                $skipped++;
                $errors[] = "Erreur avec le devis #" . $quote->getId() . ": " . $e->getMessage();
                
                // Mettre explicitement le statut à 'pending' pour éviter les problèmes
                try {
                    $quote->setPaymentStatus('pending');
                } catch (\Throwable $e2) {
                    // Si même cette opération échoue, juste continuer
                }
            }
            
            $io->progressAdvance();
        }
        
        try {
            // Sauvegarder les modifications
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $io->error("Erreur lors de la sauvegarde des modifications: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        $io->progressFinish();
        
        if ($updated > 0) {
            $io->success(sprintf('Payment status recalculated for %d quotes.', $updated));
        }
        
        if ($skipped > 0) {
            $io->warning(sprintf('%d quotes were skipped due to errors.', $skipped));
            foreach ($errors as $error) {
                $io->text($error);
            }
        }
        
        return Command::SUCCESS;
    }
} 