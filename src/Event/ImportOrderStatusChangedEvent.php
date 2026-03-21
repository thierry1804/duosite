<?php

namespace App\Event;

use App\Entity\ImportOrder;
use Symfony\Contracts\EventDispatcher\Event;

class ImportOrderStatusChangedEvent extends Event
{
    public const NAME = 'import_order.status_changed';

    public function __construct(
        private ImportOrder $order,
        private string $oldStatus,
        private string $newStatus,
        private string $changedBy,
        private ?string $comment = null
    ) {}

    public function getOrder(): ImportOrder
    {
        return $this->order;
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
}
