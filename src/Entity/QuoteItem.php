<?php

namespace App\Entity;

use App\Repository\QuoteItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: QuoteItemRepository::class)]
#[ORM\Table(name: 'quote_items')]
#[ORM\HasLifecycleCallbacks]
class QuoteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quote $quote = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de produit est obligatoire')]
    private ?string $productType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $otherProductType = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description du produit est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le budget doit être positif ou nul')]
    private ?float $budget = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoFilename = null;

    /**
     * @var File|null
     */
    private $photoFile;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(string $productType): self
    {
        $this->productType = $productType;

        return $this;
    }

    public function getOtherProductType(): ?string
    {
        return $this->otherProductType;
    }

    public function setOtherProductType(?string $otherProductType): self
    {
        $this->otherProductType = $otherProductType;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile): self
    {
        $this->photoFile = $photoFile;

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
} 