<?php

namespace App\Entity;

use App\Repository\ProductProposalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProductProposalRepository::class)]
#[ORM\Table(name: 'product_proposals')]
#[ORM\HasLifecycleCallbacks]
class ProductProposal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productProposals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteOffer $offer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuoteItem $quoteItem = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $minPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $maxPrice = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'json')]
    private array $images = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $dimensions = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $weight = null;

    /**
     * @var array<\Symfony\Component\HttpFoundation\File\UploadedFile>|null
     * Propriété non-persistée, utilisée uniquement pour le formulaire et le transfert de fichiers
     */
    #[Assert\All([
        new Assert\Image([
            'maxSize' => '5M',
            'mimeTypes' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ],
            'mimeTypesMessage' => 'Formats autorisés : JPG, PNG, GIF, WEBP'
        ])
    ])]
    private $imageFiles = [];

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

    public function getQuoteItem(): ?QuoteItem
    {
        return $this->quoteItem;
    }

    public function setQuoteItem(?QuoteItem $quoteItem): self
    {
        $this->quoteItem = $quoteItem;
        return $this;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function setMinPrice(?float $minPrice): self
    {
        $this->minPrice = $minPrice;
        return $this;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?float $maxPrice): self
    {
        $this->maxPrice = $maxPrice;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function addImage(string $image): self
    {
        if (!in_array($image, $this->images)) {
            $this->images[] = $image;
        }
        return $this;
    }

    public function removeImage(string $image): self
    {
        $key = array_search($image, $this->images);
        if ($key !== false) {
            unset($this->images[$key]);
            $this->images = array_values($this->images);
        }
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

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): self
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return array<\Symfony\Component\HttpFoundation\File\UploadedFile>|null
     */
    public function getImageFiles(): ?array
    {
        return $this->imageFiles;
    }

    /**
     * @param array<\Symfony\Component\HttpFoundation\File\UploadedFile>|null $imageFiles
     */
    public function setImageFiles(?array $imageFiles): self
    {
        $this->imageFiles = $imageFiles;
        return $this;
    }

    public function getPriceRange(): string
    {
        if ($this->minPrice === null && $this->maxPrice === null) {
            return 'Non défini';
        }
        
        if ($this->minPrice === $this->maxPrice || $this->maxPrice === null) {
            return number_format($this->minPrice, 2) . ' €';
        }
        
        if ($this->minPrice === null) {
            return 'Jusqu\'à ' . number_format($this->maxPrice, 2) . ' €';
        }
        
        return number_format($this->minPrice, 2) . ' € - ' . number_format($this->maxPrice, 2) . ' €';
    }
} 