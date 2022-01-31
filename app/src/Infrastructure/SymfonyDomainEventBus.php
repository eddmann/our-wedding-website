<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\DomainEvent;
use App\Domain\Helpers\DomainEventBus;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyDomainEventBus implements DomainEventBus
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function publish(DomainEvent $event): void
    {
        $this->bus->dispatch($event);
    }
}
