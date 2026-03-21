<?php

namespace App\Entity;

use App\Repository\ImportOrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImportOrderItemRepository::class)]
#[ORM\Table(name: 'import_order_items')]
class ImportOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ImportOrder $importOrder = null;

    #[ORM\Column(length: 20)]
    private ?string $productCode = null;

    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private ?string $productPrice = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La couleur est obligatoire')]
    private ?string $color = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\Positive(message: 'La quantité doit être au moins 1')]
    private int $quantity = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productPhotoFilename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImportOrder(): ?ImportOrder
    {
        return $this->importOrder;
    }

    public function setImportOrder(?ImportOrder $importOrder): static
    {
        $this->importOrder = $importOrder;
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

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getLineTotal(): string
    {
        if ($this->productPrice === null) {
            return '0';
        }
        return bcmul($this->productPrice, (string) $this->quantity, 2);
    }

    public function getProductPhotoFilename(): ?string
    {
        return $this->productPhotoFilename;
    }

    public function setProductPhotoFilename(?string $productPhotoFilename): static
    {
        $this->productPhotoFilename = $productPhotoFilename;
        return $this;
    }
}
