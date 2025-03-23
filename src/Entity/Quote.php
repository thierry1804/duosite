<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    #[ORM\Column(name: "shipping_method", type: "json", nullable: true)]
    private array $shippingMethod = [];

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

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ["default" => false])]
    private bool $processed = false;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending';

    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    /**
     * @var string|array|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $originalUserData = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'not_required'])]
    private string $paymentStatus = 'not_required';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $transactionReference = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteOffer::class, orphanRemoval: true)]
    private Collection $offers;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generateQuoteNumber();
        $this->items = new ArrayCollection();
        $this->offers = new ArrayCollection();
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

    #[ORM\PreUpdate]
    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function setPhone(?string $phone): self
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

    public function getShippingMethod(): array
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(array $shippingMethod): self
    {
        $this->shippingMethod = $shippingMethod;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
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

    /**
     * @return Collection<int, QuoteItem>
     */
    public function getItems(): Collection
    {
        // Initialiser la collection si elle n'est pas encore initialisée
        if (!isset($this->items)) {
            $this->items = new ArrayCollection();
        }
        return $this->items;
    }

    public function addItem(QuoteItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setQuote($this);
        }

        return $this;
    }

    public function removeItem(QuoteItem $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getQuote() === $this) {
                $item->setQuote(null);
            }
        }

        return $this;
    }

    /**
     * Get originalUserData value
     */
    public function getOriginalUserData(): ?array
    {
        // Si la valeur est une chaîne JSON, la convertir en tableau
        if (is_string($this->originalUserData) && !empty($this->originalUserData)) {
            return json_decode($this->originalUserData, true);
        }
        
        return $this->originalUserData;
    }
    
    /**
     * Set originalUserData value
     */
    public function setOriginalUserData($originalUserData): self
    {
        // Si un tableau est fourni, le stocker tel quel
        // La conversion JSON se fera automatiquement par Doctrine
        $this->originalUserData = $originalUserData;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function isPaymentRequired(): bool
    {
        return $this->paymentStatus !== 'not_required';
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'completed';
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function setTransactionReference(?string $transactionReference): self
    {
        $this->transactionReference = $transactionReference;
        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(?\DateTimeInterface $paymentDate): self
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    /**
     * @return Collection<int, QuoteOffer>
     */
    public function getOffers(): Collection
    {
        // Initialiser la collection si elle n'est pas encore initialisée
        if (!isset($this->offers)) {
            $this->offers = new ArrayCollection();
        }
        return $this->offers;
    }

    public function addOffer(QuoteOffer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setQuote($this);
        }

        return $this;
    }

    public function removeOffer(QuoteOffer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getQuote() === $this) {
                $offer->setQuote(null);
            }
        }

        return $this;
    }
} 