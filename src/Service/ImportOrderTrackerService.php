<?php

namespace App\Service;

use App\Entity\ImportOrder;
use App\Entity\ImportOrderStatusHistory;
use App\Event\ImportOrderStatusChangedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ImportOrderTrackerService
{
    public const VALID_STATUSES = [
        'registered' => 'Enregistrée',
        'paid' => 'Payée',
        'shipped_from_china' => 'Expédiée de Chine',
        'in_transit' => 'En transit',
        'delivered' => 'Livrée',
        'canceled' => 'Annulée',
    ];

    public const ALLOWED_TRANSITIONS = [
        'registered' => ['paid', 'canceled'],
        'paid' => ['shipped_from_china', 'canceled'],
        'shipped_from_china' => ['in_transit', 'delivered', 'canceled'],
        'in_transit' => ['delivered', 'canceled'],
        'delivered' => [],
        'canceled' => [],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private TokenStorageInterface $tokenStorage
    ) {}

    public function changeStatus(
        ImportOrder $order,
        string $newStatus,
        ?string $comment = null,
        ?string $changedBy = null
    ): ImportOrder {
        $oldStatus = $order->getStatus();

        if (!array_key_exists($newStatus, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException(sprintf('Statut invalide: %s', $newStatus));
        }
        if (!isset(self::ALLOWED_TRANSITIONS[$oldStatus]) || !in_array($newStatus, self::ALLOWED_TRANSITIONS[$oldStatus], true)) {
            throw new \InvalidArgumentException(sprintf('Transition non autorisée de "%s" vers "%s"', $oldStatus, $newStatus));
        }
        if ($oldStatus === $newStatus) {
            return $order;
        }

        if ($changedBy === null) {
            $user = $this->tokenStorage->getToken()?->getUser();
            $changedBy = $user && method_exists($user, 'getEmail') ? $user->getEmail() : 'system';
        }

        $order->setStatus($newStatus);

        $history = new ImportOrderStatusHistory();
        $history->setImportOrder($order);
        $history->setOldStatus($oldStatus);
        $history->setNewStatus($newStatus);
        $history->setChangedBy($changedBy);
        $history->setComment($comment);
        $order->addStatusHistory($history);

        $this->entityManager->persist($history);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $event = new ImportOrderStatusChangedEvent($order, $oldStatus, $newStatus, $changedBy, $comment);
        $this->eventDispatcher->dispatch($event, ImportOrderStatusChangedEvent::NAME);

        return $order;
    }

    public function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::VALID_STATUSES);
    }

    public function getPossibleTransitions(string $fromStatus): array
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$fromStatus])) {
            return [];
        }
        $result = [];
        foreach (self::ALLOWED_TRANSITIONS[$fromStatus] as $status) {
            $result[$status] = self::VALID_STATUSES[$status];
        }
        return $result;
    }
}
