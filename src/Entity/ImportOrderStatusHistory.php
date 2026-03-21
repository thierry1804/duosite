<?php

namespace App\Entity;

use App\Repository\ImportOrderStatusHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportOrderStatusHistoryRepository::class)]
#[ORM\Table(name: 'import_order_status_history')]
class ImportOrderStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportOrder::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ImportOrder $importOrder = null;

    #[ORM\Column(length: 64)]
    private ?string $oldStatus = null;

    #[ORM\Column(length: 64)]
    private ?string $newStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $changedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

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

    public function getImportOrder(): ?ImportOrder
    {
        return $this->importOrder;
    }

    public function setImportOrder(?ImportOrder $importOrder): static
    {
        $this->importOrder = $importOrder;
        return $this;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): static
    {
        $this->oldStatus = $oldStatus;
        return $this;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): static
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }

    public function setChangedBy(?string $changedBy): static
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getNewStatusLabel(): string
    {
        return self::getStatusLabel($this->newStatus ?? '');
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'registered' => 'Enregistrée',
            'paid' => 'Payée',
            'shipped_from_china' => 'Expédiée de Chine',
            'in_transit' => 'En transit',
            'delivered' => 'Livrée',
            'canceled' => 'Annulée',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }
}
