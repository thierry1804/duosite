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

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int|string $dayOfWeek): self
    {
        $this->dayOfWeek = (int) $dayOfWeek;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function getOpenTime(): ?\DateTimeInterface
    {
        return $this->openTime;
    }

    public function setOpenTime(?\DateTimeInterface $openTime): self
    {
        $this->openTime = $openTime;

        return $this;
    }

    public function getCloseTime(): ?\DateTimeInterface
    {
        return $this->closeTime;
    }

    public function setCloseTime(?\DateTimeInterface $closeTime): self
    {
        $this->closeTime = $closeTime;

        return $this;
    }
}
