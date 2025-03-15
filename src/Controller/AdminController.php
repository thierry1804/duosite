<?php

namespace App\Controller;

use App\Entity\QuoteSettings;
use App\Form\QuoteSettingsType;
use App\Repository\QuoteSettingsRepository;
use App\Repository\UserRepository;
use App\Service\UserIdentityTracker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/dashboard.html.twig');
    }
    
    #[Route('/quote-settings', name: 'app_admin_quote_settings')]
    public function quoteSettings(
        Request $request, 
        QuoteSettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $settings = $settingsRepository->getSettings();
        
        $form = $this->createForm(QuoteSettingsType::class, $settings);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Les paramètres de devis ont été mis à jour avec succès.');
            
            return $this->redirectToRoute('app_admin_quote_settings');
        }
        
        return $this->render('admin/quote_settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/suspicious', name: 'app_admin_suspicious_users')]
    public function suspiciousUsers(
        UserRepository $userRepository,
        UserIdentityTracker $identityTracker
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();
        $suspiciousUsers = [];
        
        foreach ($users as $user) {
            $fraudCheck = $identityTracker->detectPotentialFraud($user);
            
            if ($fraudCheck['suspiciousActivity']) {
                $suspiciousUsers[] = [
                    'user' => $user,
                    'fraudDetails' => $fraudCheck
                ];
            }
        }
        
        return $this->render('admin/suspicious_users.html.twig', [
            'suspiciousUsers' => $suspiciousUsers
        ]);
    }
} 