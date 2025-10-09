<?php

namespace App\Event;

use App\Entity\Quote;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché lors d'un changement de statut de devis
 */
class QuoteStatusChangedEvent extends Event
{
    public const NAME = 'quote.status_changed';

    public function __construct(
        private Quote $quote,
        private string $oldStatus,
        private string $newStatus,
        private string $changedBy,
        private ?string $comment = null
    ) {}

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getChangedBy(): string
    {
        return $this->changedBy;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Retourne les données de l'événement sous forme de tableau
     */
    public function toArray(): array
    {
        return [
            'quote_id' => $this->quote->getId(),
            'quote_number' => $this->quote->getQuoteNumber(),
            'tracking_token' => $this->quote->getTrackingToken(),
            'customer_email' => $this->quote->getEmail(),
            'customer_name' => $this->quote->getFullName(),
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy,
            'comment' => $this->comment,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ];
    }

    /**
     * Retourne les données de l'événement sous forme de JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Vérifie si le changement de statut nécessite une notification client
     */
    public function requiresClientNotification(): bool
    {
        // Statuts qui nécessitent une notification au client
        $clientNotificationStatuses = [
            'in_progress',
            'waiting_customer',
            'completed',
            'rejected',
            'accepted',
            'declined',
            'shipped',
            'delivered',
            'canceled'
        ];

        return in_array($this->newStatus, $clientNotificationStatuses);
    }

    /**
     * Vérifie si le changement de statut nécessite une notification admin
     */
    public function requiresAdminNotification(): bool
    {
        // Statuts qui nécessitent une notification à l'admin
        $adminNotificationStatuses = [
            'accepted',
            'declined',
            'canceled'
        ];

        return in_array($this->newStatus, $adminNotificationStatuses);
    }

    /**
     * Retourne le type de notification basé sur le nouveau statut
     */
    public function getNotificationType(): string
    {
        return match ($this->newStatus) {
            'in_progress' => 'info',
            'waiting_customer' => 'warning',
            'completed' => 'success',
            'rejected' => 'error',
            'accepted' => 'success',
            'declined' => 'warning',
            'shipped' => 'info',
            'delivered' => 'success',
            'canceled' => 'error',
            default => 'info'
        };
    }

    /**
     * Retourne le message de notification basé sur le nouveau statut
     */
    public function getNotificationMessage(): string
    {
        return match ($this->newStatus) {
            'in_progress' => 'Votre devis est maintenant en cours de traitement.',
            'waiting_customer' => 'Nous attendons votre réponse concernant le devis.',
            'completed' => 'Votre devis a été traité avec succès.',
            'rejected' => 'Votre devis a été rejeté.',
            'accepted' => 'Votre devis a été accepté.',
            'declined' => 'Votre devis a été refusé.',
            'shipped' => 'Votre commande a été expédiée.',
            'delivered' => 'Votre commande a été livrée.',
            'canceled' => 'Votre devis a été annulé.',
            default => 'Le statut de votre devis a été mis à jour.'
        };
    }

    /**
     * Retourne le sujet de l'email basé sur le nouveau statut
     */
    public function getEmailSubject(): string
    {
        $quoteNumber = $this->quote->getQuoteNumber();
        
        return match ($this->newStatus) {
            'in_progress' => "Devis #{$quoteNumber} - En cours de traitement",
            'waiting_customer' => "Devis #{$quoteNumber} - Action requise",
            'completed' => "Devis #{$quoteNumber} - Traité avec succès",
            'rejected' => "Devis #{$quoteNumber} - Rejeté",
            'accepted' => "Devis #{$quoteNumber} - Accepté",
            'declined' => "Devis #{$quoteNumber} - Refusé",
            'shipped' => "Commande #{$quoteNumber} - Expédiée",
            'delivered' => "Commande #{$quoteNumber} - Livrée",
            'canceled' => "Devis #{$quoteNumber} - Annulé",
            default => "Devis #{$quoteNumber} - Mise à jour du statut"
        };
    }
}
