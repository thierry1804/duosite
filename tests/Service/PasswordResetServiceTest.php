<?php

namespace App\Tests\Service;

use App\Entity\PasswordResetChallenge;
use App\Entity\User;
use App\Repository\PasswordResetChallengeRepository;
use App\Repository\UserRepository;
use App\Service\PasswordResetMailer;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetServiceTest extends TestCase
{
    private const APP_SECRET = 'test-secret';

    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private PasswordResetChallengeRepository&MockObject $challengeRepository;
    private PasswordResetMailer&MockObject $mailer;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private PasswordResetService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->challengeRepository = $this->createMock(PasswordResetChallengeRepository::class);
        $this->mailer = $this->createMock(PasswordResetMailer::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new PasswordResetService(
            $this->entityManager,
            $this->userRepository,
            $this->challengeRepository,
            $this->mailer,
            $this->passwordHasher,
            self::APP_SECRET
        );
    }

    private function makeClient(string $email = 'client@example.com', bool $enabled = true): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Jean');
        $user->setLastName('Dupont');
        $user->setPassword('hashed');
        $user->setRoles(['ROLE_USER']);
        $user->setIsEnabled($enabled);

        return $user;
    }

    private function makeAdmin(string $email = 'admin@example.com'): User
    {
        $user = $this->makeClient($email);
        $user->setRoles(['ROLE_ADMIN']);

        return $user;
    }

    public function testStartResetEligibleClientCreatesChallengeAndSendsMail(): void
    {
        $user = $this->makeClient();
        $this->userRepository->method('findOneBy')->with(['email' => 'client@example.com'])->willReturn($user);
        $this->challengeRepository->expects($this->once())->method('invalidateOpenChallengesForUser')->with($user);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PasswordResetChallenge::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->mailer->expects($this->once())->method('sendOtpCode')->with($user, $this->matchesRegularExpression('/^\d{6}$/'));

        $result = $this->service->startReset('client@example.com');

        $this->assertTrue($result['challengeCreated']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['token']);
    }

    public function testStartResetUnknownEmailReturnsDummyTokenWithoutMail(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->mailer->expects($this->never())->method('sendOtpCode');
        $this->entityManager->expects($this->never())->method('persist');

        $result = $this->service->startReset('unknown@example.com');

        $this->assertFalse($result['challengeCreated']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['token']);
    }

    public function testStartResetAdminReturnsDummyTokenWithoutMail(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeAdmin());
        $this->mailer->expects($this->never())->method('sendOtpCode');
        $this->entityManager->expects($this->never())->method('persist');

        $result = $this->service->startReset('admin@example.com');

        $this->assertFalse($result['challengeCreated']);
    }

    public function testStartResetDisabledUserReturnsDummyTokenWithoutMail(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeClient('client@example.com', false));
        $this->mailer->expects($this->never())->method('sendOtpCode');

        $result = $this->service->startReset('client@example.com');

        $this->assertFalse($result['challengeCreated']);
    }

    public function testVerifyOtpAcceptsValidCode(): void
    {
        $user = $this->makeClient();
        $otp = '123456';
        $challenge = new PasswordResetChallenge(
            $user,
            'token123',
            $this->service->hashOtpCode($otp),
            new \DateTimeImmutable('+15 minutes')
        );
        $this->challengeRepository->method('findActiveByPublicToken')->with('token123')->willReturn($challenge);

        $this->assertTrue($this->service->verifyOtp('token123', $otp));
    }

    public function testVerifyOtpRejectsInvalidCodeAndIncrementsAttempts(): void
    {
        $user = $this->makeClient();
        $challenge = new PasswordResetChallenge(
            $user,
            'token123',
            $this->service->hashOtpCode('123456'),
            new \DateTimeImmutable('+15 minutes')
        );
        $this->challengeRepository->method('findActiveByPublicToken')->willReturn($challenge);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertFalse($this->service->verifyOtp('token123', '000000'));
        $this->assertSame(1, $challenge->getAttempts());
    }

    public function testVerifyOtpLocksAfterFiveFailures(): void
    {
        $user = $this->makeClient();
        $challenge = new PasswordResetChallenge(
            $user,
            'token123',
            $this->service->hashOtpCode('123456'),
            new \DateTimeImmutable('+15 minutes')
        );
        $this->challengeRepository->method('findActiveByPublicToken')->willReturn($challenge);
        $this->entityManager->expects($this->exactly(5))->method('flush');

        for ($i = 0; $i < 5; ++$i) {
            $this->assertFalse($this->service->verifyOtp('token123', '000000'));
        }

        $this->assertTrue($challenge->hasExceededAttempts());
        $this->assertNotNull($challenge->getConsumedAt());
    }

    public function testResendRespectsCooldown(): void
    {
        $user = $this->makeClient();
        $challenge = new PasswordResetChallenge(
            $user,
            'token123',
            $this->service->hashOtpCode('123456'),
            new \DateTimeImmutable('+15 minutes')
        );
        $this->challengeRepository->method('findActiveByPublicToken')->willReturn($challenge);
        $this->mailer->expects($this->never())->method('sendOtpCode');

        $result = $this->service->resendOtp('token123');

        $this->assertFalse($result['ok']);
        $this->assertSame('cooldown', $result['error']);
        $this->assertNotNull($result['retryAfter']);
        $this->assertGreaterThan(0, $result['retryAfter']);
    }

    public function testResetPasswordConsumesChallenge(): void
    {
        $user = $this->makeClient();
        $challenge = new PasswordResetChallenge(
            $user,
            'token123',
            $this->service->hashOtpCode('123456'),
            new \DateTimeImmutable('+15 minutes')
        );
        $this->challengeRepository->method('findActiveByPublicToken')->willReturn($challenge);
        $this->passwordHasher->method('hashPassword')->willReturn('new-hash');
        $this->challengeRepository->expects($this->once())->method('invalidateOpenChallengesForUser')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->service->resetPassword('token123', 'newpassword'));
        $this->assertSame('new-hash', $user->getPassword());
        $this->assertNotNull($challenge->getConsumedAt());
    }

    public function testResetPasswordRejectsWithoutActiveChallenge(): void
    {
        $this->challengeRepository->method('findActiveByPublicToken')->willReturn(null);
        $this->passwordHasher->expects($this->never())->method('hashPassword');

        $this->assertFalse($this->service->resetPassword('missing', 'newpassword'));
    }
}
