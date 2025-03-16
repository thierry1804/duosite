<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager): Response
    {
        // Si l'utilisateur est déjà connecté, le rediriger vers la page appropriée
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            return $this->redirectToRoute('app_user_profile');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut rester vide - elle sera interceptée par la clé de déconnexion du pare-feu
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/login-success', name: 'app_login_success')]
    public function loginSuccess(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            // Mettre à jour la date de dernière connexion
            $user->setLastLoginAt(new \DateTimeImmutable());
            $entityManager->flush();
      
            // Rediriger les administrateurs vers le tableau de bord admin
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
        }

        // Rediriger les utilisateurs normaux vers leur profil
        return $this->redirectToRoute('app_user_profile');
    }
} 