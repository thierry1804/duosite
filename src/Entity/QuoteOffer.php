<?php

namespace App\Entity;

use App\Repository\QuoteOfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteOfferRepository::class)]
#[ORM\Table(name: 'quote_offers')]
#[ORM\HasLifecycleCallbacks]
class QuoteOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quote $quote = null;

    #[ORM\Column(length: 50)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: ProductProposal::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $productProposals;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: ShippingOption::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $shippingOptions;

    #[ORM\Column(length: 20)]
    private ?string $status = 'draft';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    private ?string $rmbMgaExchangeRate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFilePath = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->productProposals = new ArrayCollection();
        $this->shippingOptions = new ArrayCollection();
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): self
    {
        $this->quote = $quote;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    /**
     * @return Collection<int, ProductProposal>
     */
    public function getProductProposals(): Collection
    {
        if (!isset($this->productProposals)) {
            $this->productProposals = new ArrayCollection();
        }
        return $this->productProposals;
    }

    public function addProductProposal(ProductProposal $productProposal): self
    {
        if (!$this->productProposals->contains($productProposal)) {
            $this->productProposals->add($productProposal);
            $productProposal->setOffer($this);
        }

        return $this;
    }

    public function removeProductProposal(ProductProposal $productProposal): self
    {
        if ($this->productProposals->removeElement($productProposal)) {
            // set the owning side to null (unless already changed)
            if ($productProposal->getOffer() === $this) {
                $productProposal->setOffer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ShippingOption>
     */
    public function getShippingOptions(): Collection
    {
        if (!isset($this->shippingOptions)) {
            $this->shippingOptions = new ArrayCollection();
        }
        return $this->shippingOptions;
    }

    public function addShippingOption(ShippingOption $shippingOption): self
    {
        if (!$this->shippingOptions->contains($shippingOption)) {
            $this->shippingOptions->add($shippingOption);
            $shippingOption->setOffer($this);
        }

        return $this;
    }

    public function removeShippingOption(ShippingOption $shippingOption): self
    {
        if ($this->shippingOptions->removeElement($shippingOption)) {
            // set the owning side to null (unless already changed)
            if ($shippingOption->getOffer() === $this) {
                $shippingOption->setOffer(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getRmbMgaExchangeRate(): ?string
    {
        return $this->rmbMgaExchangeRate;
    }

    public function setRmbMgaExchangeRate(?string $rmbMgaExchangeRate): self
    {
        $this->rmbMgaExchangeRate = $rmbMgaExchangeRate;
        return $this;
    }

    public function getPdfFilePath(): ?string
    {
        return $this->pdfFilePath;
    }

    public function setPdfFilePath(?string $pdfFilePath): self
    {
        $this->pdfFilePath = $pdfFilePath;
        return $this;
    }

    public function calculateTotalPrice(): float
    {
        $total = 0.0;
        
        // Calculer le total des propositions de produits (prix unitaire moyen * quantité)
        foreach ($this->productProposals as $proposal) {
            if ($proposal->getMinPrice() && $proposal->getMaxPrice()) {
                $avgPrice = ($proposal->getMinPrice() + $proposal->getMaxPrice()) / 2;
                $total += $avgPrice * ($proposal->getQuoteItem() ? ($proposal->getQuoteItem()->getQuantity() ?: 1) : 1);
            }
        }
        
        // Ajouter les frais d'expédition
        foreach ($this->shippingOptions as $shippingOption) {
            $total += $shippingOption->getPrice() ?: 0;
        }
        
        $this->totalPrice = $total;
        return $total;
    }
} 