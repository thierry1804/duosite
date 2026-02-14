<?php

namespace App\Controller;

use App\Entity\QuoteSettings;
use App\Entity\Quote;
use App\Entity\User;
use App\Form\QuoteSettingsType;
use App\Repository\QuoteSettingsRepository;
use App\Repository\UserRepository;
use App\Repository\QuoteRepository;
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
    public function dashboard(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        QuoteRepository $quoteRepository,
        UserIdentityTracker $identityTracker
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupération des statistiques (4 requêtes légères)
        $pendingQuotesCount = $quoteRepository->count(['status' => 'pending']);
        $completedQuotesCount = $quoteRepository->countCompleted();
        $totalUsersCount = $userRepository->count([]);
        
        // Devis récents avec user pré-chargé (1 requête, pas de N+1)
        $recentQuotes = $quoteRepository->findRecentWithoutOfferSentWithUser(5);
        
        // Utilisateurs suspects : 1 requête users + 1 requête tous les devis des users (au lieu de N)
        $users = $userRepository->findAll();
        $userIds = array_map(fn (User $u) => $u->getId(), $users);
        $allQuotesForUsers = $quoteRepository->findQuotesByUserIds($userIds);
        $quotesByUserId = [];
        foreach ($allQuotesForUsers as $quote) {
            $uid = $quote->getUser()?->getId();
            if ($uid !== null) {
                $quotesByUserId[$uid][] = $quote;
            }
        }
        $suspiciousUsers = [];
        foreach ($users as $user) {
            $fraudCheck = $identityTracker->detectPotentialFraudWithQuotes($user, $quotesByUserId[$user->getId()] ?? []);
            if ($fraudCheck['suspiciousActivity']) {
                $suspiciousUsers[] = [
                    'user' => $user,
                    'fraudDetails' => $fraudCheck
                ];
            }
        }
        $suspiciousUsersCount = count($suspiciousUsers);
        
        return $this->render('admin/dashboard.html.twig', [
            'pendingQuotesCount' => $pendingQuotesCount,
            'completedQuotesCount' => $completedQuotesCount,
            'totalUsersCount' => $totalUsersCount,
            'suspiciousUsersCount' => $suspiciousUsersCount,
            'recentQuotes' => $recentQuotes,
            'suspiciousUsers' => array_slice($suspiciousUsers, 0, 3) // Limiter à 3 pour l'affichage
        ]);
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
        QuoteRepository $quoteRepository,
        UserIdentityTracker $identityTracker
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();
        $userIds = array_map(fn (User $u) => $u->getId(), $users);
        $allQuotesForUsers = $quoteRepository->findQuotesByUserIds($userIds);
        $quotesByUserId = [];
        foreach ($allQuotesForUsers as $quote) {
            $uid = $quote->getUser()?->getId();
            if ($uid !== null) {
                $quotesByUserId[$uid][] = $quote;
            }
        }
        $suspiciousUsers = [];
        foreach ($users as $user) {
            $fraudCheck = $identityTracker->detectPotentialFraudWithQuotes($user, $quotesByUserId[$user->getId()] ?? []);
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