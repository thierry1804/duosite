<?php

namespace App\Entity;

use App\Repository\ImportOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ImportOrderRepository::class)]
#[ORM\Table(name: 'import_orders')]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback('validateBusinessRules')]
class ImportOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 36, unique: true)]
    private ?string $trackingToken = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le nom complet est obligatoire')]
    private ?string $fullName = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    private ?string $phone = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'L\'adresse de livraison est obligatoire')]
    private ?string $deliveryAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $productCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productName = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $productPrice = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $color = null;

    #[ORM\OneToMany(mappedBy: 'importOrder', targetEntity: ImportOrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $items;

    #[ORM\Column(length: 30)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(length: 50, options: ['default' => 'registered'])]
    private string $status = 'registered';

    #[ORM\Column]
    #[Assert\IsTrue(message: 'Vous devez accepter les conditions générales de vente')]
    private bool $cgvAccepted = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $weight = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dimensions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shippingEstimate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingInfo = null;

    #[ORM\OneToMany(mappedBy: 'importOrder', targetEntity: ImportOrderStatusHistory::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $statusHistory;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->statusHistory = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function generateOrderNumberAndToken(): void
    {
        if ($this->orderNumber === null) {
            $date = $this->createdAt->format('Ymd');
            $uniqueId = strtoupper(substr(uniqid(), -4));
            $this->orderNumber = sprintf('CMD-%s-%s', $date, $uniqueId);
        }
        if ($this->trackingToken === null) {
            $this->trackingToken = $this->generateToken();
        }
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(18));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getTrackingToken(): ?string
    {
        return $this->trackingToken;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(string $productCode): static
    {
        $this->productCode = $productCode;
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductPrice(): ?string
    {
        return $this->productPrice;
    }

    public function setProductPrice(string $productPrice): static
    {
        $this->productPrice = $productPrice;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return Collection<int, ImportOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ImportOrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setImportOrder($this);
        }
        return $this;
    }

    public function removeItem(ImportOrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getImportOrder() === $this) {
                $item->setImportOrder(null);
            }
        }
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isCgvAccepted(): bool
    {
        return $this->cgvAccepted;
    }

    public function setCgvAccepted(bool $cgvAccepted): static
    {
        $this->cgvAccepted = $cgvAccepted;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): static
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getShippingEstimate(): ?string
    {
        return $this->shippingEstimate;
    }

    public function setShippingEstimate(?string $shippingEstimate): static
    {
        $this->shippingEstimate = $shippingEstimate;
        return $this;
    }

    public function getShippingInfo(): ?string
    {
        return $this->shippingInfo;
    }

    public function setShippingInfo(?string $shippingInfo): static
    {
        $this->shippingInfo = $shippingInfo;
        return $this;
    }

    /**
     * @return Collection<int, ImportOrderStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(ImportOrderStatusHistory $statusHistory): static
    {
        if (!$this->statusHistory->contains($statusHistory)) {
            $this->statusHistory->add($statusHistory);
            $statusHistory->setImportOrder($this);
        }
        return $this;
    }

    public function getStatusLabel(): string
    {
        return ImportOrderStatusHistory::getStatusLabel($this->status);
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->paymentMethod) {
            'mvola' => 'MVola',
            'orange_money' => 'Orange Money',
            'airtel_money' => 'Airtel Money',
            'cash' => 'Paiement en espèces dans notre local',
            'mobile_money' => 'Mobile Money',
            default => $this->paymentMethod ?? '-',
        };
    }

    public static function validateBusinessRules(self $order, ExecutionContextInterface $context): void
    {
        self::validatePhone($order, $context);
        self::validatePaymentReference($order, $context);
    }

    private static function validatePhone(self $order, ExecutionContextInterface $context): void
    {
        $value = $order->getPhone();
        if ($value === null || $value === '') {
            return;
        }
        $digits = preg_replace('/[^0-9]/', '', $value);
        $len = \strlen($digits);
        // Madagascar : 020 XX XXX XX (fixe) ou 03X XX XXX XX (mobile) — 10 chiffres avec 0
        // Sans 0 : 20 + 7 chiffres ou 3X + 8 chiffres. Avec 261 : idem après 261.
        if ($len === 10 && preg_match('/^0(20\d{7}|3\d{8})$/', $digits)) {
            return;
        }
        if ($len === 9 && preg_match('/^(20\d{7}|3\d{8})$/', $digits)) {
            return;
        }
        if (preg_match('/^261(20\d{7}|3\d{8})$/', $digits)) {
            return;
        }
        $context->buildViolation('Numéro invalide. Format attendu : 020 XX XXX XX (fixe) ou 03X XX XXX XX (mobile).')
            ->atPath('phone')
            ->addViolation();
    }

    private static function validatePaymentReference(self $order, ExecutionContextInterface $context): void
    {
        if ($order->getPaymentMethod() === 'cash') {
            return;
        }

        $reference = trim((string) $order->getPaymentReference());
        if ($reference === '') {
            $context->buildViolation('La référence du paiement est obligatoire pour ce mode de paiement.')
                ->atPath('paymentReference')
                ->addViolation();
        }
    }
}
