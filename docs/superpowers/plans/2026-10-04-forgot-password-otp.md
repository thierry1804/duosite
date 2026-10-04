# Forgot Password OTP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre aux clients de réinitialiser leur mot de passe via OTP email (demande → OTP → nouveau MDP → login).

**Architecture:** Entité `PasswordResetChallenge` dédiée (token public + OTP HMAC). `PasswordResetService` gère éligibilité, création, vérif, reset. `PasswordResetMailer` envoie le code. Contrôleur public en 3 étapes avec jetons factices anti-énumération.

**Tech Stack:** Symfony 7.2, Security Bundle, Doctrine ORM, Symfony Mailer, Twig, Bootstrap (templates login existants), PHPUnit unit tests.

**Spec:** `docs/superpowers/specs/2026-10-04-forgot-password-otp-design.md`

## Global Constraints

- OTP 6 chiffres, HMAC-SHA256 avec `%kernel.secret%` / `APP_SECRET`, TTL **15 min**
- Max **5** tentatives OTP ; cooldown renvoi **60 s**
- Clients uniquement (`!isAdmin()` + `isEnabled`) ; admins exclus
- Message générique anti-énumération : « Si un compte existe, un code a été envoyé. »
- Pas de connexion auto après reset → redirect `/login`
- From email : `commercial@duoimport.mg` / `Duo Import MDG` (comme `AdminAccountMailer`)
- UI alignée sur `templates/security/login.html.twig` (card Bootstrap)
- Commits sans mention Cursor ; auteur git inchangé

## File map

| Fichier | Rôle |
|---------|------|
| `src/Entity/PasswordResetChallenge.php` | Challenge OTP |
| `src/Repository/PasswordResetChallengeRepository.php` | Lookup / invalidation |
| `migrations/Version20261004090000.php` | CREATE TABLE |
| `src/Service/PasswordResetMailer.php` | Email OTP |
| `templates/emails/password_reset_otp.html.twig` | Template mail |
| `src/Service/PasswordResetService.php` | Logique métier |
| `tests/Service/PasswordResetServiceTest.php` | Tests unitaires service |
| `src/Form/ForgotPasswordRequestType.php` | Form email |
| `src/Form/ForgotPasswordOtpType.php` | Form OTP |
| `src/Form/ForgotPasswordResetType.php` | Form nouveau MDP |
| `src/Controller/PasswordResetController.php` | Routes HTTP |
| `templates/security/forgot_password_request.html.twig` | Écran email |
| `templates/security/forgot_password_otp.html.twig` | Écran OTP |
| `templates/security/forgot_password_reset.html.twig` | Écran reset |
| `templates/security/login.html.twig` | Lien « Mot de passe oublié ? » |
| `config/packages/security.yaml` | `PUBLIC_ACCESS` sur `/forgot-password` |

---

### Task 1: Entité + repository + migration

**Files:**
- Create: `src/Entity/PasswordResetChallenge.php`
- Create: `src/Repository/PasswordResetChallengeRepository.php`
- Create: `migrations/Version20261004090000.php`

**Produces:**
- Entité complète avec getters/setters
- `PasswordResetChallengeRepository::findActiveByPublicToken(string $token): ?PasswordResetChallenge`
- `PasswordResetChallengeRepository::invalidateOpenChallengesForUser(User $user): void` (set `consumedAt = now` sur challenges ouverts)

- [ ] **Step 1: Créer l’entité**

```php
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

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getPublicToken(): string { return $this->publicToken; }
    public function getOtpCodeHash(): string { return $this->otpCodeHash; }
    public function setOtpCodeHash(string $otpCodeHash): self { $this->otpCodeHash = $otpCodeHash; return $this; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): self { $this->expiresAt = $expiresAt; return $this; }
    public function getAttempts(): int { return $this->attempts; }
    public function incrementAttempts(): self { ++$this->attempts; return $this; }
    public function resetAttempts(): self { $this->attempts = 0; return $this; }
    public function getConsumedAt(): ?\DateTimeImmutable { return $this->consumedAt; }
    public function markConsumed(?\DateTimeImmutable $at = null): self { $this->consumedAt = $at ?? new \DateTimeImmutable(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastSentAt(): \DateTimeImmutable { return $this->lastSentAt; }
    public function setLastSentAt(\DateTimeImmutable $lastSentAt): self { $this->lastSentAt = $lastSentAt; return $this; }

    public function isConsumed(): bool { return null !== $this->consumedAt; }
    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        return $this->expiresAt < ($now ?? new \DateTimeImmutable());
    }
    public function hasExceededAttempts(): bool { return $this->attempts >= self::MAX_ATTEMPTS; }
    public function isActive(?\DateTimeImmutable $now = null): bool
    {
        return !$this->isConsumed() && !$this->isExpired($now) && !$this->hasExceededAttempts();
    }
}
```

- [ ] **Step 2: Créer le repository**

```php
<?php

namespace App\Repository;

use App\Entity\PasswordResetChallenge;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetChallenge>
 */
class PasswordResetChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetChallenge::class);
    }

    public function findActiveByPublicToken(string $token): ?PasswordResetChallenge
    {
        $challenge = $this->findOneBy(['publicToken' => $token]);
        if (!$challenge || !$challenge->isActive()) {
            return null;
        }

        return $challenge;
    }

    public function invalidateOpenChallengesForUser(User $user): void
    {
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.consumedAt', ':now')
            ->where('c.user = :user')
            ->andWhere('c.consumedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
```

- [ ] **Step 3: Créer la migration**

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create password_reset_challenge table for client forgot-password OTP flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_challenge (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            public_token VARCHAR(64) NOT NULL,
            otp_code_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            attempts INT DEFAULT 0 NOT NULL,
            consumed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PWRST_USER (user_id),
            INDEX idx_password_reset_user_open (user_id, consumed_at),
            UNIQUE INDEX uniq_password_reset_public_token (public_token),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_reset_challenge ADD CONSTRAINT FK_PWRST_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_challenge DROP FOREIGN KEY FK_PWRST_USER');
        $this->addSql('DROP TABLE password_reset_challenge');
    }
}
```

- [ ] **Step 4: Appliquer la migration**

Run: `php bin/console doctrine:migrations:migrate --no-interaction`  
Expected: migration `Version20261004090000` executed

- [ ] **Step 5: Commit**

```bash
git add src/Entity/PasswordResetChallenge.php src/Repository/PasswordResetChallengeRepository.php migrations/Version20261004090000.php
git commit -m "$(cat <<'EOF'
feat: add password reset challenge entity and migration

EOF
)"
```

---

### Task 2: Mailer OTP

**Files:**
- Create: `src/Service/PasswordResetMailer.php`
- Create: `templates/emails/password_reset_otp.html.twig`

**Produces:**
- `PasswordResetMailer::sendOtpCode(User $user, string $code): void`

- [ ] **Step 1: Créer le mailer**

```php
<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PasswordResetMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function sendOtpCode(User $user, string $code): void
    {
        $html = $this->twig->render('emails/password_reset_otp.html.twig', [
            'user' => $user,
            'code' => $code,
        ]);
        $text = sprintf(
            "Bonjour %s,\n\nVoici votre code de réinitialisation de mot de passe : %s\n\nCe code expire dans 15 minutes.\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.",
            $user->getFullName(),
            $code
        );

        $email = (new Email())
            ->from(new Address('commercial@duoimport.mg', 'Duo Import MDG'))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html)
            ->text($text);

        $this->mailer->send($email);
    }
}
```

- [ ] **Step 2: Créer le template email**

```twig
<p>Bonjour {{ user.fullName }},</p>

<p>
    Voici votre code pour réinitialiser votre mot de passe :
</p>

<p><strong>{{ code }}</strong></p>

<p>Ce code expire dans 15 minutes.</p>

<p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
```

- [ ] **Step 3: Commit**

```bash
git add src/Service/PasswordResetMailer.php templates/emails/password_reset_otp.html.twig
git commit -m "$(cat <<'EOF'
feat: add password reset OTP mailer

EOF
)"
```

---

### Task 3: PasswordResetService + tests unitaires

**Files:**
- Create: `src/Service/PasswordResetService.php`
- Create: `tests/Service/PasswordResetServiceTest.php`

**Consumes:**
- `PasswordResetChallenge`, repository, `PasswordResetMailer`, `UserRepository`, `UserPasswordHasherInterface`, `EntityManagerInterface`
- Constructor param `string $appSecret` bound to `%kernel.secret%`

**Produces (signatures exactes):**
- `public const SESSION_TOKEN = 'password_reset_token';`
- `public const SESSION_VERIFIED = 'password_reset_verified_token';`
- `isEligibleClient(?User $user): bool`
- `startReset(string $email): array` → `['token' => string, 'challengeCreated' => bool]`
- `verifyOtp(string $publicToken, string $otpCode): bool`
- `resendOtp(string $publicToken): array` → `['ok' => bool, 'error' => ?string, 'retryAfter' => ?int]`
- `resetPassword(string $publicToken, string $plainPassword): bool`
- `hashOtpCode(string $otpCode): string`
- `generatePublicToken(): string`

- [ ] **Step 1: Écrire les tests unitaires (failing)**

Créer `tests/Service/PasswordResetServiceTest.php` avec mocks PHPUnit (`createMock`) de :
`EntityManagerInterface`, `UserRepository`, `PasswordResetChallengeRepository`, `PasswordResetMailer`, `UserPasswordHasherInterface`.

Couvrir au minimum :

```php
public function testStartResetEligibleClientCreatesChallengeAndSendsMail(): void
public function testStartResetUnknownEmailReturnsDummyTokenWithoutMail(): void
public function testStartResetAdminReturnsDummyTokenWithoutMail(): void
public function testStartResetDisabledUserReturnsDummyTokenWithoutMail(): void
public function testVerifyOtpAcceptsValidCode(): void
public function testVerifyOtpRejectsInvalidCodeAndIncrementsAttempts(): void
public function testVerifyOtpLocksAfterFiveFailures(): void
public function testResendRespectsCooldown(): void
public function testResetPasswordConsumesChallenge(): void
public function testResetPasswordRejectsWithoutActiveChallenge(): void
```

Pour chaque test : construire un `User` via setters (`setEmail`, `setRoles`, `setIsEnabled`, `setFirstName`, `setLastName`, `setPassword`).  
`appSecret` de test : `'test-secret'`.  
OTP attendu : générer via réflexion / méthode publique `hashOtpCode` après implémentation.

- [ ] **Step 2: Run tests — expect FAIL**

Run: `php bin/phpunit tests/Service/PasswordResetServiceTest.php`  
Expected: FAIL (class `PasswordResetService` introuvable)  
Si `phpunit` absent : `vendor/bin/phpunit` ou installer `symfony/phpunit-bridge` / `phpunit/phpunit` en require-dev avant.

- [ ] **Step 3: Implémenter `PasswordResetService`**

```php
<?php

namespace App\Service;

use App\Entity\PasswordResetChallenge;
use App\Entity\User;
use App\Repository\PasswordResetChallengeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
```

Binder `$appSecret` dans `config/services.yaml` si besoin :

```yaml
App\Service\PasswordResetService:
    arguments:
        $appSecret: '%kernel.secret%'
```

(Symfony 7 autowire souvent via param name `$appSecret` → non automatique ; binding explicite requis.)

- [ ] **Step 4: Run tests — expect PASS**

Run: `php bin/phpunit tests/Service/PasswordResetServiceTest.php`  
Expected: PASS (tous les tests verts)

- [ ] **Step 5: Commit**

```bash
git add src/Service/PasswordResetService.php tests/Service/PasswordResetServiceTest.php config/services.yaml
git commit -m "$(cat <<'EOF'
feat: add password reset service with unit tests

EOF
)"
```

---

### Task 4: Formulaires Symfony

**Files:**
- Create: `src/Form/ForgotPasswordRequestType.php`
- Create: `src/Form/ForgotPasswordOtpType.php`
- Create: `src/Form/ForgotPasswordResetType.php`

**Produces:** forms non mappés (pas d’entité)

- [ ] **Step 1: Request type**

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Email',
            'attr' => ['autocomplete' => 'email', 'placeholder' => 'Votre email'],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre email.']),
                new Email(['message' => 'Email invalide.']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
```

- [ ] **Step 2: OTP type**

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordOtpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('otpCode', TextType::class, [
            'label' => 'Code reçu par email',
            'attr' => ['autocomplete' => 'one-time-code', 'inputmode' => 'numeric'],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer le code reçu par email.']),
                new Length([
                    'min' => 6,
                    'max' => 6,
                    'exactMessage' => 'Le code doit contenir exactement {{ limit }} caractères.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
```

- [ ] **Step 3: Reset type**

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForgotPasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options' => [
                'label' => 'Nouveau mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
            'mapped' => false,
            'constraints' => [
                new NotBlank(['message' => 'Veuillez définir un mot de passe.']),
                new Length([
                    'min' => 8,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    'max' => 4096,
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Form/ForgotPasswordRequestType.php src/Form/ForgotPasswordOtpType.php src/Form/ForgotPasswordResetType.php
git commit -m "$(cat <<'EOF'
feat: add forgot-password form types

EOF
)"
```

---

### Task 5: Contrôleur + templates + login + security

**Files:**
- Create: `src/Controller/PasswordResetController.php`
- Create: `templates/security/forgot_password_request.html.twig`
- Create: `templates/security/forgot_password_otp.html.twig`
- Create: `templates/security/forgot_password_reset.html.twig`
- Modify: `templates/security/login.html.twig`
- Modify: `config/packages/security.yaml`

**Consumes:** `PasswordResetService`, forms Task 4  
**Produces:** routes `app_forgot_password`, `app_forgot_password_otp`, `app_forgot_password_reset`

- [ ] **Step 1: Ajouter access_control**

Dans `config/packages/security.yaml`, **avant** la règle `^/user` :

```yaml
- { path: ^/forgot-password, roles: PUBLIC_ACCESS }
```

- [ ] **Step 2: Créer le contrôleur**

```php
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
```

- [ ] **Step 3: Templates (même structure que login)**

`forgot_password_request.html.twig` — titre « Mot de passe oublié », `form_start` / `form_row(form.email)` / bouton « Envoyer le code », lien retour login.

`forgot_password_otp.html.twig` — titre « Vérification », `form` OTP, bouton submit « Valider », second bouton HTML `name="resend" value="1"` « Renvoyer le code » (hors `form_end` ou avec `formnovalidate` dans un form POST séparé sur la même URL). Préférer **deux** `<form method="post">` : un pour OTP (symfony form), un pour renvoi avec `_csrf_token` généré via `csrf_token('forgot_password_resend')` **ou** bouton `resend` dans le même POST (comme dans le contrôleur ci-dessus : détecter `$request->request->has('resend')` **avant** la validité OTP — déjà prévu). Ajouter dans le template :

```twig
<button class="btn btn-link" type="submit" name="resend" value="1" formnovalidate>Renvoyer le code</button>
```

à l’intérieur du même `form_start`.

`forgot_password_reset.html.twig` — titre « Nouveau mot de passe », champs repeated password, bouton « Réinitialiser ».

Afficher les flashes (`app.flashes`) dans chaque template comme ailleurs sur le site.

- [ ] **Step 4: Lien sur login**

Dans `templates/security/login.html.twig`, après le champ mot de passe (avant remember-me) :

```twig
<div class="mb-3 text-end">
    <a href="{{ path('app_forgot_password') }}">Mot de passe oublié ?</a>
</div>
```

- [ ] **Step 5: Vérification manuelle**

Run:
1. `php bin/console debug:router app_forgot_password`
2. Ouvrir `/login` → lien visible
3. Email client réel → recevoir OTP → reset → login OK
4. Email inconnu → même message + écran OTP, code échoue
5. Compte admin → pas d’email, même UX

Expected: parcours conforme à la spec.

- [ ] **Step 6: Commit**

```bash
git add src/Controller/PasswordResetController.php templates/security/forgot_password_request.html.twig templates/security/forgot_password_otp.html.twig templates/security/forgot_password_reset.html.twig templates/security/login.html.twig config/packages/security.yaml
git commit -m "$(cat <<'EOF'
feat: wire forgot-password OTP UI and routes

EOF
)"
```

---

### Task 6: Spec status + smoke final

**Files:**
- Modify: `docs/superpowers/specs/2026-10-04-forgot-password-otp-design.md` — `Statut : validé / implémenté`

- [ ] **Step 1: Mettre à jour le statut de la spec**

- [ ] **Step 2: Relancer les tests service**

Run: `php bin/phpunit tests/Service/PasswordResetServiceTest.php`  
Expected: PASS

- [ ] **Step 3: Commit docs**

```bash
git add docs/superpowers/specs/2026-10-04-forgot-password-otp-design.md
git commit -m "$(cat <<'EOF'
docs: mark forgot-password OTP spec as implemented

EOF
)"
```

---

## Spec coverage checklist

| Exigence spec | Task |
|---------------|------|
| Lien login « Mot de passe oublié ? » | 5 |
| OTP 6 chiffres email | 2, 3 |
| Clients only / anti-énumération + jeton factice | 3, 5 |
| Entité `PasswordResetChallenge` | 1 |
| Routes 3 écrans | 5 |
| Cooldown 60s / max 5 / TTL 15min | 1, 3 |
| Reset → flash + `/login` | 5 |
| Échec mailer → pas de challenge fantôme | 3 |
| Tests ciblés | 3 (+ smoke 5) |
| Hors scope admin / auto-login / bundle | respecté |

## Notes d’exécution

- Sur Windows PowerShell, les HEREDOC git du plan peuvent être remplacés par `git commit -m "message"` simple.
- Si Doctrine dump-sql diffère légèrement de la migration manuscrite, régénérer via `php bin/console doctrine:migrations:diff` et garder le résultat, à condition que le schéma corresponde à l’entité.
