<?php

namespace App\Entity;

use App\Repository\QuoteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteSettingsRepository::class)]
#[ORM\Table(name: 'quote_settings')]
class QuoteSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $freeItemsLimit = 5;

    #[ORM\Column]
    private int $itemPrice = 2000;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFreeItemsLimit(): int
    {
        return $this->freeItemsLimit;
    }

    public function setFreeItemsLimit(int $freeItemsLimit): self
    {
        $this->freeItemsLimit = $freeItemsLimit;
        return $this;
    }

    public function getItemPrice(): int
    {
        return $this->itemPrice;
    }

    public function setItemPrice(int $itemPrice): self
    {
        $this->itemPrice = $itemPrice;
        return $this;
    }
} 