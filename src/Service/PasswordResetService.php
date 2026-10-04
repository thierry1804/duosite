<?php

namespace App\Service;

use App\Entity\PasswordResetChallenge;
use App\Entity\User;
use App\Repository\PasswordResetChallengeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public const SESSION_TOKEN = 'password_reset_token';
    public const SESSION_VERIFIED = 'password_reset_verified_token';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PasswordResetChallengeRepository $challengeRepository,
        private PasswordResetMailer $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.secret%')]
        private string $appSecret,
    ) {}

    public function isEligibleClient(?User $user): bool
    {
        return null !== $user
            && !$user->isAdmin()
            && $user->isEnabled();
    }

    public function generatePublicToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashOtpCode(string $otpCode): string
    {
        return hash_hmac('sha256', $otpCode, $this->appSecret);
    }

    /**
     * @return array{token: string, challengeCreated: bool}
     */
    public function startReset(string $email): array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$this->isEligibleClient($user)) {
            return ['token' => $this->generatePublicToken(), 'challengeCreated' => false];
        }

        $this->challengeRepository->invalidateOpenChallengesForUser($user);

        $otpCode = (string) random_int(100000, 999999);
        $token = $this->generatePublicToken();
        $challenge = new PasswordResetChallenge(
            $user,
            $token,
            $this->hashOtpCode($otpCode),
            new \DateTimeImmutable(sprintf('+%d minutes', PasswordResetChallenge::TTL_MINUTES))
        );

        $this->entityManager->persist($challenge);
        $this->entityManager->flush();

        try {
            $this->mailer->sendOtpCode($user, $otpCode);
        } catch (\Throwable) {
            $this->entityManager->remove($challenge);
            $this->entityManager->flush();
            throw new \RuntimeException('MAIL_SEND_FAILED');
        }

        return ['token' => $token, 'challengeCreated' => true];
    }

    public function verifyOtp(string $publicToken, string $otpCode): bool
    {
        $challenge = $this->challengeRepository->findActiveByPublicToken($publicToken);
        if (!$challenge) {
            return false;
        }

        $valid = hash_equals($challenge->getOtpCodeHash(), $this->hashOtpCode($otpCode));
        if (!$valid) {
            $challenge->incrementAttempts();
            if ($challenge->hasExceededAttempts()) {
                $challenge->markConsumed();
            }
            $this->entityManager->flush();

            return false;
        }

        return true;
    }

    /**
     * @return array{ok: bool, error: ?string, retryAfter: ?int}
     */
    public function resendOtp(string $publicToken): array
    {
        $challenge = $this->challengeRepository->findActiveByPublicToken($publicToken);
        if (!$challenge) {
            return ['ok' => false, 'error' => 'invalid', 'retryAfter' => null];
        }

        $elapsed = (new \DateTimeImmutable())->getTimestamp() - $challenge->getLastSentAt()->getTimestamp();
        $cooldown = PasswordResetChallenge::RESEND_COOLDOWN_SECONDS;
        if ($elapsed < $cooldown) {
            return ['ok' => false, 'error' => 'cooldown', 'retryAfter' => $cooldown - $elapsed];
        }

        $otpCode = (string) random_int(100000, 999999);
        $challenge->setOtpCodeHash($this->hashOtpCode($otpCode));
        $challenge->setExpiresAt(new \DateTimeImmutable(sprintf('+%d minutes', PasswordResetChallenge::TTL_MINUTES)));
        $challenge->resetAttempts();
        $challenge->setLastSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        try {
            $this->mailer->sendOtpCode($challenge->getUser(), $otpCode);
        } catch (\Throwable) {
            return ['ok' => false, 'error' => 'mail', 'retryAfter' => null];
        }

        return ['ok' => true, 'error' => null, 'retryAfter' => null];
    }

    public function resetPassword(string $publicToken, string $plainPassword): bool
    {
        $challenge = $this->challengeRepository->findActiveByPublicToken($publicToken);
        if (!$challenge) {
            return false;
        }

        $user = $challenge->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $challenge->markConsumed();
        $this->challengeRepository->invalidateOpenChallengesForUser($user);
        $this->entityManager->flush();

        return true;
    }
}
