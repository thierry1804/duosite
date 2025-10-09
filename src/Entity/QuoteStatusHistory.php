<?php

namespace App\Entity;

use App\Repository\QuoteStatusHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteStatusHistoryRepository::class)]
#[ORM\Table(name: 'quote_status_history')]
class QuoteStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Quote $quote;

    #[ORM\Column(length: 64)]
    private string $oldStatus;

    #[ORM\Column(length: 64)]
    private string $newStatus;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $changedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;
        return $this;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(string $oldStatus): self
    {
        $this->oldStatus = $oldStatus;
        return $this;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): self
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }

    public function setChangedBy(?string $changedBy): self
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Retourne un libellé lisible pour l'ancien statut
     */
    public function getOldStatusLabel(): string
    {
        return $this->getStatusLabel($this->oldStatus);
    }

    /**
     * Retourne un libellé lisible pour le nouveau statut
     */
    public function getNewStatusLabel(): string
    {
        return $this->getStatusLabel($this->newStatus);
    }

    /**
     * Convertit un statut technique en libellé lisible
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'in_progress' => 'En cours de traitement',
            'completed' => 'Terminé',
            'rejected' => 'Rejeté',
            'new' => 'Nouveau',
            'viewed' => 'Consulté',
            'waiting_customer' => 'En attente du client',
            'accepted' => 'Accepté',
            'declined' => 'Refusé',
            'converted' => 'Converti en commande',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            'canceled' => 'Annulé',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }
}
