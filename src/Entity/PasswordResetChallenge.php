<?php

namespace App\Entity;

use App\Repository\PasswordResetChallengeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordResetChallengeRepository::class)]
#[ORM\Table(name: 'password_reset_challenge')]
#[ORM\UniqueConstraint(name: 'uniq_password_reset_public_token', columns: ['public_token'])]
#[ORM\Index(name: 'idx_password_reset_user_open', columns: ['user_id', 'consumed_at'])]
class PasswordResetChallenge
{
    public const MAX_ATTEMPTS = 5;
    public const TTL_MINUTES = 15;
    public const RESEND_COOLDOWN_SECONDS = 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'public_token', length: 64)]
    private string $publicToken;

    #[ORM\Column(name: 'otp_code_hash', length: 64)]
    private string $otpCodeHash;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastSentAt;

    public function __construct(User $user, string $publicToken, string $otpCodeHash, \DateTimeImmutable $expiresAt)
    {
        $now = new \DateTimeImmutable();
        $this->user = $user;
        $this->publicToken = $publicToken;
        $this->otpCodeHash = $otpCodeHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $now;
        $this->lastSentAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPublicToken(): string
    {
        return $this->publicToken;
    }

    public function getOtpCodeHash(): string
    {
        return $this->otpCodeHash;
    }

    public function setOtpCodeHash(string $otpCodeHash): self
    {
        $this->otpCodeHash = $otpCodeHash;

        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): self
    {
        ++$this->attempts;

        return $this;
    }

    public function resetAttempts(): self
    {
        $this->attempts = 0;

        return $this;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function markConsumed(?\DateTimeImmutable $at = null): self
    {
        $this->consumedAt = $at ?? new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastSentAt(): \DateTimeImmutable
    {
        return $this->lastSentAt;
    }

    public function setLastSentAt(\DateTimeImmutable $lastSentAt): self
    {
        $this->lastSentAt = $lastSentAt;

        return $this;
    }

    public function isConsumed(): bool
    {
        return null !== $this->consumedAt;
    }

    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt < ($now ?? new \DateTimeImmutable());
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function isActive(?\DateTimeImmutable $now = null): bool
    {
        return !$this->isConsumed() && !$this->isExpired($now) && !$this->hasExceededAttempts();
    }
}
