<?php

namespace App\Controller;

use App\Form\ForgotPasswordOtpType;
use App\Form\ForgotPasswordRequestType;
use App\Form\ForgotPasswordResetType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    public function __construct(private PasswordResetService $passwordResetService) {}

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_profile');
        }

        $form = $this->createForm(ForgotPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            try {
                $result = $this->passwordResetService->startReset($email);
            } catch (\RuntimeException $e) {
                if ('MAIL_SEND_FAILED' === $e->getMessage()) {
                    $this->addFlash('danger', 'Impossible d\'envoyer le code. Veuillez réessayer.');

                    return $this->redirectToRoute('app_forgot_password');
                }
                throw $e;
            }

            $request->getSession()->set(PasswordResetService::SESSION_TOKEN, $result['token']);
            $request->getSession()->remove(PasswordResetService::SESSION_VERIFIED);
            $this->addFlash('info', 'Si un compte existe, un code a été envoyé.');

            return $this->redirectToRoute('app_forgot_password_otp', ['token' => $result['token']]);
        }

        return $this->render('security/forgot_password_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forgot-password/otp/{token}', name: 'app_forgot_password_otp', methods: ['GET', 'POST'])]
    public function otp(string $token, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_profile');
        }

        $sessionToken = (string) $request->getSession()->get(PasswordResetService::SESSION_TOKEN, '');
        if ($sessionToken !== $token) {
            $this->addFlash('danger', 'Session de réinitialisation invalide. Veuillez recommencer.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ForgotPasswordOtpType::class);
        $form->handleRequest($request);

        if ($request->isMethod('POST') && $request->request->has('resend')) {
            $resend = $this->passwordResetService->resendOtp($token);
            if ($resend['ok']) {
                $this->addFlash('info', 'Si un compte existe, un nouveau code a été envoyé.');
            } elseif ('cooldown' === $resend['error']) {
                $this->addFlash('warning', sprintf('Veuillez patienter %d seconde(s) avant de renvoyer le code.', (int) $resend['retryAfter']));
            } elseif ('mail' === $resend['error']) {
                $this->addFlash('danger', 'Impossible d\'envoyer le code. Veuillez réessayer.');
            } else {
                $this->addFlash('danger', 'Code invalide ou expiré.');
            }

            return $this->redirectToRoute('app_forgot_password_otp', ['token' => $token]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $otp = (string) $form->get('otpCode')->getData();
            if ($this->passwordResetService->verifyOtp($token, $otp)) {
                $request->getSession()->set(PasswordResetService::SESSION_VERIFIED, $token);

                return $this->redirectToRoute('app_forgot_password_reset', ['token' => $token]);
            }
            $this->addFlash('danger', 'Code invalide ou expiré.');
        }

        return $this->render('security/forgot_password_otp.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }

    #[Route('/forgot-password/reset/{token}', name: 'app_forgot_password_reset', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_profile');
        }

        $verified = (string) $request->getSession()->get(PasswordResetService::SESSION_VERIFIED, '');
        if ($verified !== $token) {
            $this->addFlash('danger', 'Veuillez d\'abord valider le code reçu par email.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ForgotPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string) $form->get('plainPassword')->getData();
            if ($this->passwordResetService->resetPassword($token, $plain)) {
                $request->getSession()->remove(PasswordResetService::SESSION_TOKEN);
                $request->getSession()->remove(PasswordResetService::SESSION_VERIFIED);
                $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', 'Lien de réinitialisation invalide ou expiré. Veuillez recommencer.');

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
