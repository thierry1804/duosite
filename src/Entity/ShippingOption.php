<?php

namespace App\Entity;

use App\Repository\ShippingOptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShippingOptionRepository::class)]
#[ORM\Table(name: 'shipping_options')]
#[ORM\HasLifecycleCallbacks]
class ShippingOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shippingOptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteOffer $offer = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le nom de l\'option d\'expédition est obligatoire')]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix de l\'option d\'expédition est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou nul')]
    private ?string $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimatedDeliveryDays = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffer(): ?QuoteOffer
    {
        return $this->offer;
    }

    public function setOffer(?QuoteOffer $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getEstimatedDeliveryDays(): ?int
    {
        return $this->estimatedDeliveryDays;
    }

    public function setEstimatedDeliveryDays(?int $estimatedDeliveryDays): self
    {
        $this->estimatedDeliveryDays = $estimatedDeliveryDays;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getFormattedDeliveryTime(): string
    {
        if ($this->estimatedDeliveryDays === null) {
            return 'Non défini';
        }
        
        if ($this->estimatedDeliveryDays <= 1) {
            return '1 jour';
        }
        
        return $this->estimatedDeliveryDays . ' jours';
    }
} 