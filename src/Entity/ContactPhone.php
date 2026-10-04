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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function isWhatsapp(): bool
    {
        return $this->isWhatsapp;
    }

    public function setIsWhatsapp(bool $isWhatsapp): self
    {
        $this->isWhatsapp = $isWhatsapp;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

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
