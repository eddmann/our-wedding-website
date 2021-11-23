<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\DomainEvent;
use App\Domain\Helpers\DomainEventBus;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyDomainEventBus implements DomainEventBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function publish(DomainEvent $event): void
    {
        $this->bus->dispatch($event);
    }
}
