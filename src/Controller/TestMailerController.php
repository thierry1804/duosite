<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class TestMailerController extends AbstractController
{
    #[Route('/test-mailer', name: 'app_test_mailer')]
    public function index(MailerInterface $mailer, TransportInterface $transport): Response
    {
        $success = false;
        $error = null;
        
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
            $transport->send($email);
            
            $success = true;
            
            // Log du succès
            error_log('Email de test envoyé avec succès à thierry1804@gmail.com');
            
        } catch (\Exception $e) {
            // Log de l'erreur
            $error = $e->getMessage();
            error_log('Erreur lors de l\'envoi de l\'email de test: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
        }
        
        // Afficher une page de confirmation
        return $this->render('test_mailer/index.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
} 