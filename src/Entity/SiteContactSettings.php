<?php

namespace App\Entity;

use App\Repository\SiteContactSettingsRepository;
use App\Validator\Constraints\UniquePhoneFlags;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteContactSettingsRepository::class)]
#[ORM\Table(name: 'site_contact_settings')]
#[UniquePhoneFlags]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddressLines(): string
    {
        return $this->addressLines;
    }

    public function setAddressLines(string $addressLines): self
    {
        $this->addressLines = $addressLines;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getWhatsappDefaultMessage(): ?string
    {
        return $this->whatsappDefaultMessage;
    }

    public function setWhatsappDefaultMessage(?string $whatsappDefaultMessage): self
    {
        $this->whatsappDefaultMessage = $whatsappDefaultMessage;

        return $this;
    }

    public function getMapEmbedUrl(): ?string
    {
        return $this->mapEmbedUrl;
    }

    public function setMapEmbedUrl(?string $mapEmbedUrl): self
    {
        $this->mapEmbedUrl = $mapEmbedUrl;

        return $this;
    }

    /** @return Collection<int, ContactPhone> */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addPhone(ContactPhone $phone): self
    {
        if (!$this->phones->contains($phone)) {
            $this->phones->add($phone);
            $phone->setSettings($this);
        }

        return $this;
    }

    public function removePhone(ContactPhone $phone): self
    {
        if ($this->phones->removeElement($phone)) {
            if ($phone->getSettings() === $this) {
                $phone->setSettings(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, OpeningHour> */
    public function getOpeningHours(): Collection
    {
        return $this->openingHours;
    }

    public function addOpeningHour(OpeningHour $openingHour): self
    {
        if (!$this->openingHours->contains($openingHour)) {
            $this->openingHours->add($openingHour);
            $openingHour->setSettings($this);
        }

        return $this;
    }

    public function removeOpeningHour(OpeningHour $openingHour): self
    {
        if ($this->openingHours->removeElement($openingHour)) {
            if ($openingHour->getSettings() === $this) {
                $openingHour->setSettings(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, SocialLink> */
    public function getSocialLinks(): Collection
    {
        return $this->socialLinks;
    }

    public function addSocialLink(SocialLink $socialLink): self
    {
        if (!$this->socialLinks->contains($socialLink)) {
            $this->socialLinks->add($socialLink);
            $socialLink->setSettings($this);
        }

        return $this;
    }

    public function removeSocialLink(SocialLink $socialLink): self
    {
        if ($this->socialLinks->removeElement($socialLink)) {
            if ($socialLink->getSettings() === $this) {
                $socialLink->setSettings(null);
            }
        }

        return $this;
    }
}
