<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Quote
{
    private ?string $quoteNumber = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas un email valide')]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-9+\s-]+$/', message: 'Le numéro de téléphone n\'est pas valide')]
    private ?string $phone = null;

    private ?string $company = null;

    #[Assert\NotBlank(message: 'Le type de produit est obligatoire')]
    private ?string $productType = null;

    private ?string $otherProductType = null;

    #[Assert\NotBlank(message: 'La description du produit est obligatoire')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères')]
    private ?string $productDescription = null;

    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    private ?int $quantity = null;

    private ?float $budget = null;

    #[Assert\NotBlank(message: 'Le délai est obligatoire')]
    private ?string $timeline = null;

    /**
     * @var array<string>
     */
    private array $services = [];

    private ?string $additionalInfo = null;

    private ?string $referralSource = null;

    #[Assert\NotBlank(message: 'Vous devez accepter la politique de confidentialité')]
    #[Assert\IsTrue(message: 'Vous devez accepter la politique de confidentialité')]
    private bool $privacyPolicy = false;

    private ?\DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateQuoteNumber();
    }

    private function generateQuoteNumber(): void
    {
        // Format: DUO-YYYYMMDD-XXXX
        $date = $this->createdAt->format('Ymd');
        $uniqueId = substr(uniqid(), -4); // Prend les 4 derniers caractères de l'ID unique
        $this->quoteNumber = sprintf('DUO-%s-%s', $date, strtoupper($uniqueId));
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
} 