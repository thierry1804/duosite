<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email, function (string $userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
                }

                if (!$user->isEnabled()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas encore activé. Vérifiez vos emails.');
                }

                // Ne bloquer que si l’invitation par email est encore en cours (jeton présent).
                // Les admins actifs sans adminActivatedAt (ex. comptes antérieurs au flux OTP) restent autorisés.
                if (
                    $user->isAdmin()
                    && null === $user->getAdminActivatedAt()
                    && null !== $user->getAdminInvitationToken()
                ) {
                    throw new CustomUserMessageAuthenticationException('Activation administrateur incomplète. Vérifiez vos emails.');
                }

                return $user;
            }),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Rediriger vers la page de succès de connexion qui mettra à jour la date de dernière connexion
        return new RedirectResponse($this->urlGenerator->generate('app_login_success'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
} 