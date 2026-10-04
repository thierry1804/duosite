# Site Contact Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendre paramétrables depuis le backoffice les coordonnées publiques (adresse, téléphones + flags WhatsApp/principal, email, horaires par jour, réseaux sociaux, carte, message WhatsApp) et les consommer sur footer, contact, WhatsApp flottant, tarifs et devis/Mobile Money.

**Architecture:** Singleton Doctrine `SiteContactSettings` avec collections normalisées (`ContactPhone`, `OpeningHour`, `SocialLink`). Lecture via `SiteContactProvider` exposé en global Twig `site_contact`. Admin `/admin/site-contact` avec formulaire collections + contraintes d’unicité des flags.

**Tech Stack:** Symfony 7.2, Doctrine ORM 3, Symfony Form (CollectionType), Twig (`GlobalsInterface`), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-04-site-contact-settings-design.md`

## Global Constraints

- Accès admin : `ROLE_ADMIN` uniquement
- Au plus un téléphone `isWhatsapp=true` et un `isPrimary=true`
- ≥ 1 téléphone requis à la sauvegarde admin
- Bouton WhatsApp flottant **masqué** si aucun `isWhatsapp`
- Primary absent → premier téléphone par `position`
- Hors scope : emails métier, PDF, `_service_suspended_notice`, pages légales
- Seed = valeurs actuelles du site (3 tél, Facebook, horaires Lun–Ven 08:00–17:00)
- Commits sans mention Cursor ; auteur git inchangé
- Tests unitaires provider en PHPUnit pur (mocks), comme `PasswordResetServiceTest`

## File map

| Fichier | Rôle |
|---------|------|
| `src/Entity/SiteContactSettings.php` | Singleton + collections |
| `src/Entity/ContactPhone.php` | Téléphone + flags |
| `src/Entity/OpeningHour.php` | Jour + plage |
| `src/Entity/SocialLink.php` | Réseau + URL |
| `src/Repository/SiteContactSettingsRepository.php` | `getSettings()` + seed défaut |
| `src/Service/SiteContactProvider.php` | API lecture + formatage + URLs |
| `tests/Service/SiteContactProviderTest.php` | Tests unitaires provider |
| `src/Form/SiteContactSettingsType.php` | Formulaire admin racine |
| `src/Form/ContactPhoneType.php` | Ligne téléphone |
| `src/Form/OpeningHourType.php` | Ligne horaire |
| `src/Form/SocialLinkType.php` | Ligne réseau |
| `src/Validator/UniquePhoneFlags.php` + `UniquePhoneFlagsValidator.php` | Contrainte ≥1 tél + flags uniques |
| `src/Controller/AdminSiteContactController.php` | Route admin |
| `templates/admin/site_contact.html.twig` | UI admin |
| `templates/admin/base.html.twig` | Lien sidebar |
| `src/Twig/SiteContactExtension.php` | Global Twig `site_contact` |
| `migrations/Version20261004090000.php` | CREATE + seed |
| `templates/base.html.twig` | Footer + WhatsApp |
| `templates/contact/index.html.twig` | Coordonnées + carte + socials |
| `templates/tarifs/index.html.twig` | Pills téléphones |
| `templates/quote/index.html.twig` | Mobile Money + lien WhatsApp |
| `src/Controller/QuoteController.php` | Remplacer `%app.primary_contact_phone%` |

---

### Task 1: Entités + repository

**Files:**
- Create: `src/Entity/SiteContactSettings.php`
- Create: `src/Entity/ContactPhone.php`
- Create: `src/Entity/OpeningHour.php`
- Create: `src/Entity/SocialLink.php`
- Create: `src/Repository/SiteContactSettingsRepository.php`

**Interfaces:**
- Consumes: rien
- Produces:
  - `SiteContactSettings` avec `getAddressLines/setAddressLines`, `getEmail/setEmail`, `getWhatsappDefaultMessage/setWhatsappDefaultMessage`, `getMapEmbedUrl/setMapEmbedUrl`, collections `phones` / `openingHours` / `socialLinks` (+ add/remove)
  - `ContactPhone`: `getLabel/setLabel`, `getNumber/setNumber`, `isWhatsapp/setIsWhatsapp`, `isPrimary/setIsPrimary`, `getPosition/setPosition`, `getSettings/setSettings`
  - `OpeningHour`: `getDayOfWeek/setDayOfWeek` (1–7), `isClosed/setIsClosed`, `getOpenTime/setOpenTime`, `getCloseTime/setCloseTime`
  - `SocialLink`: constantes réseau + `getNetwork/setNetwork`, `getUrl/setUrl`, `getPosition/setPosition`
  - `SiteContactSettings::createWithDefaults(): self` — seed mémoire
  - `SiteContactSettingsRepository::getSettings(): SiteContactSettings`

- [ ] **Step 1: Créer `ContactPhone`**

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contact_phone')]
class ContactPhone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'phones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SiteContactSettings $settings = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 50)]
    private string $number = '';

    #[ORM\Column]
    private bool $isWhatsapp = false;

    #[ORM\Column]
    private bool $isPrimary = false;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getSettings(): ?SiteContactSettings { return $this->settings; }
    public function setSettings(?SiteContactSettings $settings): self { $this->settings = $settings; return $this; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }
    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): self { $this->number = $number; return $this; }
    public function isWhatsapp(): bool { return $this->isWhatsapp; }
    public function setIsWhatsapp(bool $isWhatsapp): self { $this->isWhatsapp = $isWhatsapp; return $this; }
    public function isPrimary(): bool { return $this->isPrimary; }
    public function setIsPrimary(bool $isPrimary): self { $this->isPrimary = $isPrimary; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
```

- [ ] **Step 2: Créer `OpeningHour`**

```php
<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'opening_hour')]
#[ORM\UniqueConstraint(name: 'uniq_opening_hour_day', columns: ['settings_id', 'day_of_week'])]
class OpeningHour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'openingHours')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SiteContactSettings $settings = null;

    /** 1 = Lundi … 7 = Dimanche */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $dayOfWeek = 1;

    #[ORM\Column]
    private bool $isClosed = false;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $openTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closeTime = null;

    public function getId(): ?int { return $this->id; }
    public function getSettings(): ?SiteContactSettings { return $this->settings; }
    public function setSettings(?SiteContactSettings $settings): self { $this->settings = $settings; return $this; }
    public function getDayOfWeek(): int { return $this->dayOfWeek; }
    public function setDayOfWeek(int $dayOfWeek): self { $this->dayOfWeek = $dayOfWeek; return $this; }
    public function isClosed(): bool { return $this->isClosed; }
    public function setIsClosed(bool $isClosed): self { $this->isClosed = $isClosed; return $this; }
    public function getOpenTime(): ?\DateTimeInterface { return $this->openTime; }
    public function setOpenTime(?\DateTimeInterface $openTime): self { $this->openTime = $openTime; return $this; }
    public function getCloseTime(): ?\DateTimeInterface { return $this->closeTime; }
    public function setCloseTime(?\DateTimeInterface $closeTime): self { $this->closeTime = $closeTime; return $this; }
}
```

- [ ] **Step 3: Créer `SocialLink`**

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'social_link')]
class SocialLink
{
    public const NETWORK_FACEBOOK = 'facebook';
    public const NETWORK_TIKTOK = 'tiktok';
    public const NETWORK_INSTAGRAM = 'instagram';
    public const NETWORK_LINKEDIN = 'linkedin';
    public const NETWORK_YOUTUBE = 'youtube';
    public const NETWORK_X = 'x';
    public const NETWORK_OTHER = 'other';

    public const NETWORKS = [
        self::NETWORK_FACEBOOK => 'Facebook',
        self::NETWORK_TIKTOK => 'TikTok',
        self::NETWORK_INSTAGRAM => 'Instagram',
        self::NETWORK_LINKEDIN => 'LinkedIn',
        self::NETWORK_YOUTUBE => 'YouTube',
        self::NETWORK_X => 'X',
        self::NETWORK_OTHER => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'socialLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SiteContactSettings $settings = null;

    #[ORM\Column(length: 32)]
    private string $network = self::NETWORK_FACEBOOK;

    #[ORM\Column(length: 500)]
    private string $url = '';

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getSettings(): ?SiteContactSettings { return $this->settings; }
    public function setSettings(?SiteContactSettings $settings): self { $this->settings = $settings; return $this; }
    public function getNetwork(): string { return $this->network; }
    public function setNetwork(string $network): self { $this->network = $network; return $this; }
    public function getUrl(): string { return $this->url; }
    public function setUrl(string $url): self { $this->url = $url; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
```

- [ ] **Step 4: Créer `SiteContactSettings` avec `createWithDefaults()`**

```php
<?php

namespace App\Entity;

use App\Repository\SiteContactSettingsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteContactSettingsRepository::class)]
#[ORM\Table(name: 'site_contact_settings')]
class SiteContactSettings
{
    public const DEFAULT_MAP_EMBED_URL = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15103.66132288122!2d47.4960002156964!3d-18.829813907803317!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x21f08167b8dda017%3A0x3494ab1afb7a0b7a!2sAntsakambahiny%2C%20Antananarivo!5e0!3m2!1sen!2smg!4v1768666799526!5m2!1sen!2smg';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $addressLines = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappDefaultMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mapEmbedUrl = null;

    /** @var Collection<int, ContactPhone> */
    #[ORM\OneToMany(mappedBy: 'settings', targetEntity: ContactPhone::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $phones;

    /** @var Collection<int, OpeningHour> */
    #[ORM\OneToMany(mappedBy: 'settings', targetEntity: OpeningHour::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['dayOfWeek' => 'ASC'])]
    private Collection $openingHours;

    /** @var Collection<int, SocialLink> */
    #[ORM\OneToMany(mappedBy: 'settings', targetEntity: SocialLink::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $socialLinks;

    public function __construct()
    {
        $this->phones = new ArrayCollection();
        $this->openingHours = new ArrayCollection();
        $this->socialLinks = new ArrayCollection();
    }

    public static function createWithDefaults(): self
    {
        $s = new self();
        $s->setAddressLines("Antsakambahiny\nAmbohijanahary Antehiroka\nAntananarivo - Madagascar");
        $s->setEmail('contact@duoimport.mg');
        $s->setWhatsappDefaultMessage('Bonjour Duo Import MDG, ');
        $s->setMapEmbedUrl(self::DEFAULT_MAP_EMBED_URL);

        $phones = [
            ['label' => 'Thierry', 'number' => '+261 38 42 711 68', 'wa' => true, 'primary' => true],
            ['label' => null, 'number' => '+261 33 64 554 78', 'wa' => false, 'primary' => false],
            ['label' => null, 'number' => '+261 32 22 136 82', 'wa' => false, 'primary' => false],
        ];
        foreach ($phones as $i => $p) {
            $phone = (new ContactPhone())
                ->setLabel($p['label'])
                ->setNumber($p['number'])
                ->setIsWhatsapp($p['wa'])
                ->setIsPrimary($p['primary'])
                ->setPosition($i);
            $s->addPhone($phone);
        }

        $open = new \DateTimeImmutable('08:00');
        $close = new \DateTimeImmutable('17:00');
        for ($day = 1; $day <= 7; $day++) {
            $hour = (new OpeningHour())->setDayOfWeek($day);
            if ($day <= 5) {
                $hour->setIsClosed(false)->setOpenTime($open)->setCloseTime($close);
            } else {
                $hour->setIsClosed(true)->setOpenTime(null)->setCloseTime(null);
            }
            $s->addOpeningHour($hour);
        }

        $fb = (new SocialLink())
            ->setNetwork(SocialLink::NETWORK_FACEBOOK)
            ->setUrl('https://www.facebook.com/duoimportmdg')
            ->setPosition(0);
        $s->addSocialLink($fb);

        return $s;
    }

    public function ensureDefaultOpeningHours(): void
    {
        if ($this->openingHours->count() >= 7) {
            return;
        }
        $existing = [];
        foreach ($this->openingHours as $h) {
            $existing[$h->getDayOfWeek()] = true;
        }
        $open = new \DateTimeImmutable('08:00');
        $close = new \DateTimeImmutable('17:00');
        for ($day = 1; $day <= 7; $day++) {
            if (isset($existing[$day])) {
                continue;
            }
            $hour = (new OpeningHour())->setDayOfWeek($day);
            if ($day <= 5) {
                $hour->setIsClosed(false)->setOpenTime($open)->setCloseTime($close);
            } else {
                $hour->setIsClosed(true);
            }
            $this->addOpeningHour($hour);
        }
    }

    // getters/setters + addPhone/removePhone + addOpeningHour/removeOpeningHour + addSocialLink/removeSocialLink
    // (pattern Doctrine : setSettings($this) dans chaque add*)
}
```


- [ ] **Step 5: Créer le repository**

```php
<?php

namespace App\Repository;

use App\Entity\SiteContactSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteContactSettings>
 */
class SiteContactSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContactSettings::class);
    }

    public function getSettings(): SiteContactSettings
    {
        $settings = $this->createQueryBuilder('s')
            ->leftJoin('s.phones', 'p')->addSelect('p')
            ->leftJoin('s.openingHours', 'h')->addSelect('h')
            ->leftJoin('s.socialLinks', 'l')->addSelect('l')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$settings) {
            $settings = SiteContactSettings::createWithDefaults();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add src/Entity/SiteContactSettings.php src/Entity/ContactPhone.php src/Entity/OpeningHour.php src/Entity/SocialLink.php src/Repository/SiteContactSettingsRepository.php
git commit -m "feat: add site contact settings entities"
```

---

### Task 2: `SiteContactProvider` (TDD)

**Files:**
- Create: `tests/Service/SiteContactProviderTest.php`
- Create: `src/Service/SiteContactProvider.php`

**Interfaces:**
- Consumes: `SiteContactSettingsRepository::getSettings()`
- Produces (API publique du provider) :
  - `getSettings(): SiteContactSettings`
  - `getPhones(): list<ContactPhone>`
  - `getWhatsappPhone(): ?ContactPhone`
  - `getPrimaryPhone(): ?ContactPhone`
  - `getOpeningHours(): list<OpeningHour>`
  - `getSocialLinks(): list<SocialLink>` (URL non vide uniquement)
  - `getAddressLinesList(): list<string>`
  - `toTelHref(string $number): string` — `tel:+261...` digits avec `+` si présent
  - `toDigits(string $number): string` — chiffres seuls
  - `getWhatsappUrl(): ?string` — `https://wa.me/{digits}?text=...` ou null
  - `formatFooterHours(): string` — ex. `Lun - Ven: 8h00 - 17h00`
  - `formatContactHoursLines(): list<string>` — regroupement + jours fermés
  - `socialIconClass(string $network): string` — classe Font Awesome (`facebook-f`, `tiktok`, …)

- [ ] **Step 1: Écrire les tests qui échouent**

```php
<?php

namespace App\Tests\Service;

use App\Entity\ContactPhone;
use App\Entity\OpeningHour;
use App\Entity\SiteContactSettings;
use App\Entity\SocialLink;
use App\Repository\SiteContactSettingsRepository;
use App\Service\SiteContactProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiteContactProviderTest extends TestCase
{
    private SiteContactSettingsRepository&MockObject $repository;
    private SiteContactProvider $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SiteContactSettingsRepository::class);
        $this->provider = new SiteContactProvider($this->repository);
    }

    private function settingsWithPhones(array $defs): SiteContactSettings
    {
        $s = new SiteContactSettings();
        $s->setEmail('contact@duoimport.mg');
        $s->setWhatsappDefaultMessage('Bonjour Duo Import MDG, ');
        foreach ($defs as $i => $d) {
            $p = (new ContactPhone())
                ->setNumber($d['number'])
                ->setIsWhatsapp($d['wa'] ?? false)
                ->setIsPrimary($d['primary'] ?? false)
                ->setPosition($i)
                ->setLabel($d['label'] ?? null);
            $s->addPhone($p);
        }
        return $s;
    }

    public function testGetWhatsappPhoneReturnsFlaggedNumber(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 33 64 554 78'],
            ['number' => '+261 38 42 711 68', 'wa' => true],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('+261 38 42 711 68', $this->provider->getWhatsappPhone()?->getNumber());
    }

    public function testGetPrimaryPhoneFallsBackToFirst(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 33 64 554 78'],
            ['number' => '+261 38 42 711 68'],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('+261 33 64 554 78', $this->provider->getPrimaryPhone()?->getNumber());
    }

    public function testGetWhatsappUrlBuildsWaMeLink(): void
    {
        $s = $this->settingsWithPhones([
            ['number' => '+261 38 42 711 68', 'wa' => true],
        ]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame(
            'https://wa.me/261384271168?text=Bonjour%20Duo%20Import%20MDG%2C%20',
            $this->provider->getWhatsappUrl()
        );
    }

    public function testGetWhatsappUrlReturnsNullWhenNoWhatsapp(): void
    {
        $s = $this->settingsWithPhones([['number' => '+261 33 64 554 78']]);
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertNull($this->provider->getWhatsappUrl());
    }

    public function testFormatFooterHoursGroupsWeekdays(): void
    {
        $s = SiteContactSettings::createWithDefaults();
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertSame('Lun - Ven: 8h00 - 17h00', $this->provider->formatFooterHours());
    }

    public function testGetSocialLinksSkipsEmptyUrl(): void
    {
        $s = new SiteContactSettings();
        $s->addSocialLink((new SocialLink())->setNetwork('facebook')->setUrl('https://www.facebook.com/duoimportmdg')->setPosition(0));
        $s->addSocialLink((new SocialLink())->setNetwork('tiktok')->setUrl('')->setPosition(1));
        $this->repository->method('getSettings')->willReturn($s);
        $this->assertCount(1, $this->provider->getSocialLinks());
    }
}
```

- [ ] **Step 2: Lancer les tests — échec attendu**

Run: `php bin/phpunit tests/Service/SiteContactProviderTest.php`
Expected: FAIL (classe `SiteContactProvider` introuvable)

- [ ] **Step 3: Implémenter `SiteContactProvider`**

```php
<?php

namespace App\Service;

use App\Entity\ContactPhone;
use App\Entity\OpeningHour;
use App\Entity\SiteContactSettings;
use App\Entity\SocialLink;
use App\Repository\SiteContactSettingsRepository;

class SiteContactProvider
{
    private const DAY_SHORT = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
    private const DAY_LONG = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    public function __construct(private readonly SiteContactSettingsRepository $repository)
    {
    }

    public function getSettings(): SiteContactSettings
    {
        return $this->repository->getSettings();
    }

    /** @return list<ContactPhone> */
    public function getPhones(): array
    {
        return $this->getSettings()->getPhones()->toArray();
    }

    public function getWhatsappPhone(): ?ContactPhone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isWhatsapp()) {
                return $phone;
            }
        }
        return null;
    }

    public function getPrimaryPhone(): ?ContactPhone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isPrimary()) {
                return $phone;
            }
        }
        $phones = $this->getPhones();
        return $phones[0] ?? null;
    }

    /** @return list<OpeningHour> */
    public function getOpeningHours(): array
    {
        return $this->getSettings()->getOpeningHours()->toArray();
    }

    /** @return list<SocialLink> */
    public function getSocialLinks(): array
    {
        return array_values(array_filter(
            $this->getSettings()->getSocialLinks()->toArray(),
            static fn (SocialLink $l) => trim($l->getUrl()) !== ''
        ));
    }

    /** @return list<string> */
    public function getAddressLinesList(): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $this->getSettings()->getAddressLines()) ?: [];
        return array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
    }

    public function toDigits(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }

    public function toTelHref(string $number): string
    {
        $digits = $this->toDigits($number);
        return 'tel:+' . ltrim($digits, '0');
    }

    public function getWhatsappUrl(): ?string
    {
        $phone = $this->getWhatsappPhone();
        if (!$phone) {
            return null;
        }
        $digits = $this->toDigits($phone->getNumber());
        $msg = $this->getSettings()->getWhatsappDefaultMessage() ?? '';
        $query = $msg !== '' ? '?text=' . rawurlencode($msg) : '';
        return 'https://wa.me/' . $digits . $query;
    }

    public function formatFooterHours(): string
    {
        $groups = $this->groupOpenDays();
        if ($groups === []) {
            return '';
        }
        // Premier groupe ouvert pour le footer compact
        $g = $groups[0];
        return sprintf('%s: %s', $g['label_short'], $g['hours']);
    }

    /** @return list<string> */
    public function formatContactHoursLines(): array
    {
        $lines = [];
        foreach ($this->groupAllDays() as $g) {
            $lines[] = $g['is_closed']
                ? sprintf('%s: Fermé', $g['label_long'])
                : sprintf('%s: %s', $g['label_long'], $g['hours']);
        }
        return $lines;
    }

    /** @return list<array{label_short: string, label_long: string, hours: string, is_closed: bool}> */
    private function groupOpenDays(): array
    {
        return array_values(array_filter($this->groupAllDays(), static fn ($g) => !$g['is_closed']));
    }

    /**
     * Regroupe les jours consécutifs ayant la même plage (ou fermés).
     * @return list<array{label_short: string, label_long: string, hours: string, is_closed: bool}>
     */
    private function groupAllDays(): array
    {
        $hours = $this->getOpeningHours();
        usort($hours, static fn (OpeningHour $a, OpeningHour $b) => $a->getDayOfWeek() <=> $b->getDayOfWeek());
        $groups = [];
        $i = 0;
        $n = count($hours);
        while ($i < $n) {
            $start = $hours[$i];
            $j = $i;
            while ($j + 1 < $n && $this->sameSlot($hours[$j + 1], $start) && $hours[$j + 1]->getDayOfWeek() === $hours[$j]->getDayOfWeek() + 1) {
                $j++;
            }
            $end = $hours[$j];
            $labelShort = $start->getDayOfWeek() === $end->getDayOfWeek()
                ? self::DAY_SHORT[$start->getDayOfWeek()]
                : self::DAY_SHORT[$start->getDayOfWeek()] . ' - ' . self::DAY_SHORT[$end->getDayOfWeek()];
            $labelLong = $start->getDayOfWeek() === $end->getDayOfWeek()
                ? self::DAY_LONG[$start->getDayOfWeek()]
                : self::DAY_LONG[$start->getDayOfWeek()] . ' - ' . self::DAY_LONG[$end->getDayOfWeek()];
            $groups[] = [
                'label_short' => $labelShort,
                'label_long' => $labelLong,
                'hours' => $start->isClosed() ? 'Fermé' : $this->formatTimeRange($start),
                'is_closed' => $start->isClosed(),
            ];
            $i = $j + 1;
        }
        return $groups;
    }

    private function sameSlot(OpeningHour $a, OpeningHour $b): bool
    {
        if ($a->isClosed() && $b->isClosed()) {
            return true;
        }
        if ($a->isClosed() || $b->isClosed()) {
            return false;
        }
        return $this->formatTimeRange($a) === $this->formatTimeRange($b);
    }

    private function formatTimeRange(OpeningHour $h): string
    {
        $fmt = static function (?\DateTimeInterface $t): string {
            if (!$t) {
                return '';
            }
            return ((int) $t->format('G')) . 'h' . $t->format('i');
        };

        return $fmt($h->getOpenTime()) . ' - ' . $fmt($h->getCloseTime());
    }

    public function socialIconClass(string $network): string
    {
        return match ($network) {
            SocialLink::NETWORK_FACEBOOK => 'facebook-f',
            SocialLink::NETWORK_X => 'x-twitter',
            SocialLink::NETWORK_OTHER => 'link',
            default => $network,
        };
    }
}
```

Corriger `formatTimeRange` pour produire `8h00` (pas `8h0`) : minutes toujours sur 2 chiffres via `format('i')` — OK. Heure : `8h00` = `(int)G` + `h` + `i`.

- [ ] **Step 4: Relancer les tests**

Run: `php bin/phpunit tests/Service/SiteContactProviderTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add src/Service/SiteContactProvider.php tests/Service/SiteContactProviderTest.php
git commit -m "feat: add SiteContactProvider for public contact data"
```

---

### Task 3: Migration + seed

**Files:**
- Create: `migrations/Version20261004090000.php`

**Interfaces:**
- Consumes: schéma des 4 tables Task 1
- Produces: tables + 1 ligne settings seedée (équivalente à `createWithDefaults()`)

- [ ] **Step 1: Générer puis ajuster la migration**

Run: `php bin/console doctrine:migrations:diff`
Puis renommer / éditer pour `Version20261004090000` si besoin, et **ajouter le seed SQL/PHP** dans `up()` après CREATE :

En PHP dans `up()` après schema :

```php
// Via connection insert, ou mieux : laisser getSettings() seed au premier hit.
// Préférer seed explicite dans up() pour prod immédiatement cohérente :
$this->addSql("INSERT INTO site_contact_settings (address_lines, email, whatsapp_default_message, map_embed_url) VALUES (...)");
// + INSERT phones / hours / social
```

Utiliser des paramètres bound via `$this->connection->insert(...)` si `addSql` devient illisible — acceptable dans une migration Doctrine.

Valeurs seed = constantes de `SiteContactSettings::createWithDefaults()` / `DEFAULT_MAP_EMBED_URL`.

- [ ] **Step 2: Exécuter la migration en local**

Run: `php bin/console doctrine:migrations:migrate --no-interaction`
Expected: OK, tables créées, seed présent

- [ ] **Step 3: Vérifier**

Run: `php bin/console dbal:run-sql "SELECT email FROM site_contact_settings"`
Expected: `contact@duoimport.mg`

- [ ] **Step 4: Commit**

```bash
git add migrations/Version20261004090000.php
git commit -m "feat: migrate and seed site contact settings tables"
```

---

### Task 4: Formulaires + validation

**Files:**
- Create: `src/Validator/Constraints/UniquePhoneFlags.php`
- Create: `src/Validator/Constraints/UniquePhoneFlagsValidator.php`
- Create: `src/Form/ContactPhoneType.php`
- Create: `src/Form/OpeningHourType.php`
- Create: `src/Form/SocialLinkType.php`
- Create: `src/Form/SiteContactSettingsType.php`
- Create: `tests/Validator/UniquePhoneFlagsValidatorTest.php`

**Interfaces:**
- Consumes: entités Task 1
- Produces: forms `data_class` correspondants ; contrainte class-level sur `SiteContactSettings` :
  - phones count ≥ 1
  - count(`isWhatsapp`) ≤ 1
  - count(`isPrimary`) ≤ 1
  - chaque `OpeningHour` ouvert : times non null et open < close

- [ ] **Step 1: Contrainte + test unitaire du validator**

```php
// UniquePhoneFlags.php
#[\Attribute(\Attribute::TARGET_CLASS)]
class UniquePhoneFlags extends Constraint
{
    public string $noPhoneMessage = 'Ajoutez au moins un numéro de téléphone.';
    public string $whatsappMessage = 'Un seul numéro peut être marqué WhatsApp.';
    public string $primaryMessage = 'Un seul numéro peut être marqué principal.';
    public function getTargets(): string { return self::CLASS_CONSTRAINT; }
}

// UniquePhoneFlagsValidator.php — valide SiteContactSettings
public function validate(mixed $value, Constraint $constraint): void
{
    if (!$value instanceof SiteContactSettings || !$constraint instanceof UniquePhoneFlags) {
        return;
    }
    $phones = $value->getPhones();
    if ($phones->count() < 1) {
        $this->context->buildViolation($constraint->noPhoneMessage)->atPath('phones')->addViolation();
    }
    $wa = 0; $pr = 0;
    foreach ($phones as $p) {
        if ($p->isWhatsapp()) { $wa++; }
        if ($p->isPrimary()) { $pr++; }
    }
    if ($wa > 1) {
        $this->context->buildViolation($constraint->whatsappMessage)->atPath('phones')->addViolation();
    }
    if ($pr > 1) {
        $this->context->buildViolation($constraint->primaryMessage)->atPath('phones')->addViolation();
    }
}
```

Test PHPUnit : construire `SiteContactSettings` avec 0 / 2 WhatsApp / 2 primary et assert violations via `ExecutionContext` mock ou validator Symfony — pattern simple : appeler `validate` avec un context mock qui compte `buildViolation`.

- [ ] **Step 2: Types de formulaire**

`ContactPhoneType` : `label` (TextType, optional), `number` (TextType, NotBlank), `isWhatsapp` (CheckboxType), `isPrimary` (CheckboxType), `position` (HiddenType).

`OpeningHourType` : `dayOfWeek` (HiddenType), affichage label jour en template, `isClosed` (CheckboxType), `openTime` / `closeTime` (TimeType, widget single_text). Callback : si `!isClosed` alors times required et open < close.

`SocialLinkType` : `network` (ChoiceType::choices flipped `SocialLink::NETWORKS`), `url` (UrlType, default_protocol https), `position` (HiddenType).

`SiteContactSettingsType` :
- `addressLines` TextareaType
- `email` EmailType + Email constraint
- `whatsappDefaultMessage` TextType optional
- `mapEmbedUrl` UrlType optional
- `phones` CollectionType (`entry_type` ContactPhoneType, allow_add/delete, by_reference false)
- `openingHours` CollectionType (allow_add false, allow_delete false)
- `socialLinks` CollectionType allow_add/delete
- Attribut `#[UniquePhoneFlags]` sur l’entité `SiteContactSettings` (ou `constraints` dans `configureOptions`)

- [ ] **Step 3: Commit**

```bash
git add src/Form src/Validator tests/Validator
git commit -m "feat: add site contact admin forms and phone flag validation"
```

---

### Task 5: Admin controller + template + menu

**Files:**
- Create: `src/Controller/AdminSiteContactController.php`
- Create: `templates/admin/site_contact.html.twig`
- Modify: `templates/admin/base.html.twig` (après lien Paramètres des devis, avant Pages légales)

**Interfaces:**
- Consumes: `SiteContactSettingsRepository::getSettings()`, `SiteContactSettingsType`
- Produces: route `app_admin_site_contact` → `/admin/site-contact`

- [ ] **Step 1: Controller**

```php
<?php

namespace App\Controller;

use App\Form\SiteContactSettingsType;
use App\Repository\SiteContactSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminSiteContactController extends AbstractController
{
    #[Route('/site-contact', name: 'app_admin_site_contact')]
    public function edit(
        Request $request,
        SiteContactSettingsRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $settings = $repository->getSettings();
        $settings->ensureDefaultOpeningHours();

        $form = $this->createForm(SiteContactSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Renormaliser positions 0..n-1 sur phones et socialLinks
            $i = 0;
            foreach ($settings->getPhones() as $phone) {
                $phone->setPosition($i++);
            }
            $i = 0;
            foreach ($settings->getSocialLinks() as $link) {
                $link->setPosition($i++);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Les coordonnées du site ont été mises à jour avec succès.');
            return $this->redirectToRoute('app_admin_site_contact');
        }

        return $this->render('admin/site_contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

- [ ] **Step 2: Template admin** (Bootstrap 5, style `quote_settings`)

Sections : Identité (adresse, email, message WA, carte) → Téléphones (prototype JS collection Symfony) → Horaires (7 lignes) → Réseaux. Bouton « Enregistrer ».

Pour les collections add/remove : utiliser le pattern Symfony officiel `data-prototype` + petit JS inline (comme ailleurs dans le projet s’il existe ; sinon snippet Bootstrap standard).

Afficher le libellé du jour en lecture seule à côté de chaque `OpeningHour` (mapper 1→Lundi dans Twig).

- [ ] **Step 3: Menu**

Dans `templates/admin/base.html.twig`, après « Paramètres des devis » :

```twig
<li>
    <a href="{{ path('app_admin_site_contact') }}" class="{{ app.request.get('_route') == 'app_admin_site_contact' ? 'active' : '' }}">
        <i data-lucide="contact"></i> <span>Coordonnées du site</span>
    </a>
</li>
```

- [ ] **Step 4: Smoke manuel**

Run: ouvrir `/admin/site-contact` connecté admin → modifier un téléphone → Enregistrer → flash succès.

- [ ] **Step 5: Commit**

```bash
git add src/Controller/AdminSiteContactController.php templates/admin/site_contact.html.twig templates/admin/base.html.twig
git commit -m "feat: add admin page for site contact settings"
```

---

### Task 6: Global Twig `site_contact`

**Files:**
- Create: `src/Twig/SiteContactExtension.php`

**Interfaces:**
- Consumes: `SiteContactProvider`
- Produces: global Twig `site_contact` = instance du provider

- [ ] **Step 1: Extension**

```php
<?php

namespace App\Twig;

use App\Service\SiteContactProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SiteContactExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly SiteContactProvider $siteContactProvider)
    {
    }

    public function getGlobals(): array
    {
        return [
            'site_contact' => $this->siteContactProvider,
        ];
    }
}
```

Autowire Symfony détecte l’extension automatiquement.

- [ ] **Step 2: Commit**

```bash
git add src/Twig/SiteContactExtension.php
git commit -m "feat: expose site_contact Twig global"
```

---

### Task 7: Brancher les templates publics + devis

**Files:**
- Modify: `templates/base.html.twig` (footer social ~417–426, contact ~453–477, WhatsApp ~503–508)
- Modify: `templates/contact/index.html.twig` (~155–196, ~229)
- Modify: `templates/tarifs/index.html.twig` (~65–67)
- Modify: `templates/quote/index.html.twig` (Mobile Money + lien WA ~775, ~789)
- Modify: `src/Controller/QuoteController.php` (retirer Autowire `%app.primary_contact_phone%`, injecter `SiteContactProvider`, passer `primaryContactPhone` depuis `getPrimaryPhone()?->getNumber()`)

**Interfaces:**
- Consumes: global `site_contact` / provider methods Task 2
- Produces: plus de hardcode des 3 numéros / email / adresse / WA / carte / Facebook sur ces surfaces

- [ ] **Step 1: Footer + WhatsApp dans `base.html.twig`**

Social :

```twig
{% for link in site_contact.socialLinks %}
    <a href="{{ link.url }}" target="_blank" rel="noopener noreferrer" aria-label="Suivez-nous sur {{ link.network }}">
        <i class="fab fa-{{ site_contact.socialIconClass(link.network) }}" aria-hidden="true"></i>
        <span class="visually-hidden">{{ link.network }}</span>
    </a>
{% endfor %}
```

Contact block : boucler `site_contact.addressLinesList`, `site_contact.phones` avec `site_contact.toTelHref(phone.number)`, email, `site_contact.formatFooterHours`.

WhatsApp :

```twig
{% set waUrl = site_contact.whatsappUrl %}
{% if waUrl %}
<div class="whatsapp-button">
    <a href="{{ waUrl }}" target="_blank" rel="noopener noreferrer">
        <i data-lucide="message-circle" aria-hidden="true"></i>
    </a>
</div>
{% endif %}
```

- [ ] **Step 2: Page contact**

Remplacer adresse / tél / email / horaires (`formatContactHoursLines` joint par `<br>`) / socials / `iframe src="{{ site_contact.settings.mapEmbedUrl }}"` si non null.

- [ ] **Step 3: Tarifs**

```twig
{% for phone in site_contact.phones %}
    <span class="tarif-pill">{% if phone.label %}{{ phone.label }} · {% endif %}{{ phone.number }}</span>
{% endfor %}
```

- [ ] **Step 4: QuoteController + template devis**

```php
// Remplacer param string par :
SiteContactProvider $siteContactProvider
// ...
'primaryContactPhone' => $siteContactProvider->getPrimaryPhone()?->getNumber() ?? '',
```

Dans Twig devis : lien WhatsApp via `site_contact.whatsappUrl` (si null, masquer le lien « Une question ? WhatsApp »).

- [ ] **Step 5: Vérification manuelle**

- Footer / contact / tarifs / devis affichent les données seed
- Admin : décocher WhatsApp → bouton flottant disparaît
- Admin : changer primary → Mobile Money affiche le nouveau numéro

- [ ] **Step 6: Commit**

```bash
git add templates/base.html.twig templates/contact/index.html.twig templates/tarifs/index.html.twig templates/quote/index.html.twig src/Controller/QuoteController.php
git commit -m "feat: wire public pages to site contact settings"
```

---

### Task 8: Spec coverage check + polish

- [ ] **Step 1: Checklist spec**

| Exigence | Task |
|----------|------|
| Tables normalisées | 1, 3 |
| Flags WhatsApp / Primary | 1, 4 |
| Horaires par jour | 1, 4, 7 |
| Social dynamique | 1, 4, 7 |
| Admin backoffice | 5 |
| Provider + Twig global | 2, 6 |
| Footer / contact / WA / tarifs / devis | 7 |
| WA masqué si absent | 2, 7 |
| Primary fallback | 2 |
| Hors scope emails/PDF | non touché |

- [ ] **Step 2: Relancer tests**

Run: `php bin/phpunit tests/Service/SiteContactProviderTest.php tests/Validator/UniquePhoneFlagsValidatorTest.php`
Expected: PASS

- [ ] **Step 3: Mettre à jour le statut de la spec**

Dans `docs/superpowers/specs/2026-10-04-site-contact-settings-design.md` : `Statut : validé / implémenté`

- [ ] **Step 4: Commit final doc**

```bash
git add docs/superpowers/specs/2026-10-04-site-contact-settings-design.md
git commit -m "docs: mark site contact settings spec as implemented"
```

---

## Self-review (plan vs spec)

1. **Spec coverage :** toutes les exigences du tableau Task 8 ont une task. Hors scope respecté.
2. **Placeholders :** aucun TBD restant ; `ensureDefaultOpeningHours()` et `socialIconClass()` sont définis.
3. **Types :** `site_contact` = `SiteContactProvider` ; méthodes stables entre Task 2 et 7.

## Notes d’implémentation

- Ne pas supprimer `app.primary_contact_phone` de `services.yaml` dans cette itération (peut rester inutilisé).
- Collection prototype JS : snippet Symfony docs « form collections » dans le template admin si aucun helper existant.
