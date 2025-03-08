<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quotes')]
#[ORM\HasLifecycleCallbacks]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $quoteNumber = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas un email valide')]
    private ?string $email = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-9+\s-]+$/', message: 'Le numéro de téléphone n\'est pas valide')]
    private ?string $phone = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de produit est obligatoire')]
    private ?string $productType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $otherProductType = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description du produit est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères')]
    private ?string $productDescription = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?float $budget = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le délai est obligatoire')]
    private ?string $timeline = null;

    #[ORM\Column(type: 'json')]
    private array $services = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $referralSource = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Vous devez accepter la politique de confidentialité')]
    #[Assert\IsTrue(message: 'Vous devez accepter la politique de confidentialité')]
    private bool $privacyPolicy = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(options: ["default" => false])]
    private bool $processed = false;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateQuoteNumber();
    }

    #[ORM\PrePersist]
    private function generateQuoteNumber(): void
    {
        // Only generate if not already set
        if ($this->quoteNumber === null) {
            // Format: DUO-YYYYMMDD-XXXX
            $date = $this->createdAt->format('Ymd');
            $uniqueId = substr(uniqid(), -4); // Prend les 4 derniers caractères de l'ID unique
            $this->quoteNumber = sprintf('DUO-%s-%s', $date, strtoupper($uniqueId));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
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

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(string $productDescription): self
    {
        $this->productDescription = $productDescription;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
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

    public function getTimeline(): ?string
    {
        return $this->timeline;
    }

    public function setTimeline(string $timeline): self
    {
        $this->timeline = $timeline;
        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function setServices(array $services): self
    {
        $this->services = $services;
        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): self
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    public function getReferralSource(): ?string
    {
        return $this->referralSource;
    }

    public function setReferralSource(?string $referralSource): self
    {
        $this->referralSource = $referralSource;
        return $this;
    }

    public function isPrivacyPolicy(): bool
    {
        return $this->privacyPolicy;
    }

    public function setPrivacyPolicy(bool $privacyPolicy): self
    {
        $this->privacyPolicy = $privacyPolicy;
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

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
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
} 