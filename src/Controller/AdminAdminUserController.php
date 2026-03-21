<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminActivationType;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use App\Service\AdminAccountMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminAdminUserController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret
    ) {}

    #[Route('/admin/users/admins', name: 'app_admin_admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admins = $userRepository->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/admin_users/index.html.twig', [
            'admins' => $admins,
            'currentUserId' => $this->getUser()?->getId(),
        ]);
    }

    #[Route('/admin/users/admins/new', name: 'app_admin_admin_users_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        AdminAccountMailer $adminAccountMailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admin = new User();
        $form = $this->createForm(AdminUserType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = bin2hex(random_bytes(32));
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsEnabled(false);
            $admin->setAdminInvitationToken($token);
            $admin->setAdminInvitationExpiresAt(new \DateTimeImmutable('+48 hours'));
            $admin->setAdminOtpCodeHash(null);
            $admin->setAdminOtpExpiresAt(null);
            $admin->setAdminActivatedAt(null);

            // Mot de passe provisoire, remplacé pendant le parcours d'activation.
            $temporaryPassword = bin2hex(random_bytes(16));
            $admin->setPassword($passwordHasher->hashPassword($admin, $temporaryPassword));

            $entityManager->persist($admin);
            $entityManager->flush();

            $activationUrl = $this->generateUrl('app_admin_account_confirm', [
                'token' => $token,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            try {
                $adminAccountMailer->sendInvitation($admin, $activationUrl);
                $this->addFlash('success', 'Administrateur créé. Un email de confirmation a été envoyé.');
            } catch (\Throwable $exception) {
                $this->addFlash('warning', 'Administrateur créé, mais l\'email de confirmation n\'a pas pu être envoyé.');
            }

            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        return $this->render('admin/admin_users/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/admins/{id}/edit', name: 'app_admin_admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $admin,
        Request $request,
        EntityManagerInterface $entityManager,
        AdminAccountMailer $adminAccountMailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $admin->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas modifier votre propre compte administrateur ici.');
            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        if (!$admin->isAdmin()) {
            throw $this->createNotFoundException('Utilisateur administrateur introuvable.');
        }

        $form = $this->createForm(AdminUserType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            try {
                $adminAccountMailer->sendAccountUpdated($admin);
                $this->addFlash('success', 'Administrateur mis à jour et notification envoyée.');
            } catch (\Throwable $exception) {
                $this->addFlash('warning', 'Administrateur mis à jour, mais la notification email n\'a pas pu être envoyée.');
            }

            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        return $this->render('admin/admin_users/edit.html.twig', [
            'form' => $form->createView(),
            'admin' => $admin,
        ]);
    }

    #[Route('/admin/users/admins/{id}', name: 'app_admin_admin_users_delete', methods: ['POST'])]
    public function delete(
        User $admin,
        Request $request,
        EntityManagerInterface $entityManager,
        AdminAccountMailer $adminAccountMailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $admin->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas supprimer votre propre compte administrateur.');
            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        if (!$admin->isAdmin()) {
            throw $this->createNotFoundException('Utilisateur administrateur introuvable.');
        }

        if (!$this->isCsrfTokenValid('delete_admin_' . $admin->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        try {
            $adminAccountMailer->sendAccountDeleted($admin);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Suppression annulée: impossible d\'envoyer l\'email de notification.');
            return $this->redirectToRoute('app_admin_admin_users_index');
        }

        $entityManager->remove($admin);
        $entityManager->flush();

        $this->addFlash('success', 'Administrateur supprimé et notification envoyée.');

        return $this->redirectToRoute('app_admin_admin_users_index');
    }

    #[Route('/admin-account/confirm/{token}', name: 'app_admin_account_confirm', methods: ['GET'])]
    public function confirmInvitation(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        AdminAccountMailer $adminAccountMailer
    ): Response {
        $admin = $userRepository->findOneBy(['adminInvitationToken' => $token]);

        if (
            !$admin
            || !$admin->isAdmin()
            || !$admin->getAdminInvitationExpiresAt()
            || $admin->getAdminInvitationExpiresAt() < new \DateTimeImmutable()
        ) {
            throw $this->createNotFoundException('Lien de confirmation invalide ou expiré.');
        }

        $otpCode = (string) random_int(100000, 999999);
        $admin->setAdminOtpCodeHash($this->hashOtpCode($otpCode));
        $admin->setAdminOtpExpiresAt(new \DateTimeImmutable('+15 minutes'));
        $entityManager->flush();

        try {
            $adminAccountMailer->sendOtpCode($admin, $otpCode);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Impossible d\'envoyer le code de validation. Veuillez réessayer.');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('info', 'Un code de validation vient de vous être envoyé par email.');
        return $this->redirectToRoute('app_admin_account_activate', ['token' => $token]);
    }

    #[Route('/admin-account/activate/{token}', name: 'app_admin_account_activate', methods: ['GET', 'POST'])]
    public function activateAccount(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $admin = $userRepository->findOneBy(['adminInvitationToken' => $token]);

        if (
            !$admin
            || !$admin->isAdmin()
            || !$admin->getAdminInvitationExpiresAt()
            || $admin->getAdminInvitationExpiresAt() < new \DateTimeImmutable()
        ) {
            throw $this->createNotFoundException('Lien d\'activation invalide ou expiré.');
        }

        $form = $this->createForm(AdminActivationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $enteredCode = (string) $form->get('otpCode')->getData();
            $hashedEnteredCode = $this->hashOtpCode($enteredCode);
            $otpExpiresAt = $admin->getAdminOtpExpiresAt();

            if (
                !$admin->getAdminOtpCodeHash()
                || !hash_equals((string) $admin->getAdminOtpCodeHash(), $hashedEnteredCode)
                || !$otpExpiresAt
                || $otpExpiresAt < new \DateTimeImmutable()
            ) {
                $this->addFlash('danger', 'Code invalide ou expiré.');
            } else {
                $plainPassword = (string) $form->get('plainPassword')->getData();
                $admin->setPassword($passwordHasher->hashPassword($admin, $plainPassword));
                $admin->setIsEnabled(true);
                $admin->setAdminActivatedAt(new \DateTimeImmutable());
                $admin->setAdminInvitationToken(null);
                $admin->setAdminInvitationExpiresAt(null);
                $admin->setAdminOtpCodeHash(null);
                $admin->setAdminOtpExpiresAt(null);

                $entityManager->flush();

                $this->addFlash('success', 'Compte administrateur activé avec succès. Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('admin/admin_users/activate.html.twig', [
            'form' => $form->createView(),
            'admin' => $admin,
        ]);
    }

    private function hashOtpCode(string $otpCode): string
    {
        return hash_hmac('sha256', $otpCode, $this->appSecret);
    }
}
