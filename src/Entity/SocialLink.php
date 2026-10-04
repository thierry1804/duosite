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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettings(): ?SiteContactSettings
    {
        return $this->settings;
    }

    public function setSettings(?SiteContactSettings $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): self
    {
        $this->network = $network;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int|string|null $position): self
    {
        $this->position = (int) ($position ?? 0);

        return $this;
    }
}
