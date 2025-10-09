<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Création de l'email
                $email = (new Email())
                    ->from(new Address('noreply@duoimport.mg', 'Duo Import MDG'))
                    ->replyTo($contact->getEmail())
                    ->to('commercial@duoimport.mg')
                    ->subject('Nouveau message de contact : ' . $contact->getSubject())
                    ->html($this->renderView(
                        'emails/contact.html.twig',
                        ['contact' => $contact]
                    ));

                // Envoi de l'email
                $mailer->send($email);

                $this->addFlash('success', 'Votre message a été envoyé avec succès !');
                return $this->redirectToRoute('app_contact');
                
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.');
                return $this->redirectToRoute('app_contact');
            }
        }
        
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
} 