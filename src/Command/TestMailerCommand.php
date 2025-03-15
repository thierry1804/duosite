<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-mailer',
    description: 'Test l\'envoi d\'email',
)]
class TestMailerCommand extends Command
{
    private $mailer;
    private $transport;

    public function __construct(MailerInterface $mailer, TransportInterface $transport)
    {
        $this->mailer = $mailer;
        $this->transport = $transport;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test d\'envoi d\'email');
        
        $io->section('Configuration');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['De', 'commercial@duoimport.mg'],
                ['À', 'thierry1804@gmail.com'],
                ['Sujet', 'Test d\'envoi d\'email - ' . date('Y-m-d H:i:s')],
            ]
        );
        
        $io->section('Envoi de l\'email');
        
        try {
            // Création d'un email de test
            $email = (new Email())
                ->from('commercial@duoimport.mg')
                ->to('thierry1804@gmail.com')
                ->subject('Test d\'envoi d\'email - ' . date('Y-m-d H:i:s'))
                ->text('Ceci est un test d\'envoi d\'email depuis Duo Import MDG.')
                ->html('<p>Ceci est un test d\'envoi d\'email depuis <strong>Duo Import MDG</strong>.</p>
                       <p>Date et heure: ' . date('Y-m-d H:i:s') . '</p>
                       <p>Si vous recevez cet email, cela signifie que la configuration du mailer fonctionne correctement.</p>');
            
            // Envoi de l'email via le transport direct
            $io->text('Envoi en cours...');
            $this->transport->send($email);
            
            $io->success('L\'email a été envoyé avec succès à thierry1804@gmail.com');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur s\'est produite lors de l\'envoi de l\'email');
            $io->error($e->getMessage());
            
            $io->section('Trace de l\'erreur');
            $io->text($e->getTraceAsString());
            
            $io->section('Suggestions');
            $io->listing([
                'Vérifiez les logs du serveur pour plus de détails sur l\'erreur',
                'Vérifiez la configuration SMTP dans les fichiers .env et .env.local',
                'Assurez-vous que le serveur SMTP est correctement configuré et accessible',
                'Vérifiez les paramètres de sécurité du serveur SMTP',
            ]);
            
            return Command::FAILURE;
        }
    }
} 