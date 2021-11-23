<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventsBus;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyAggregateEventsBus implements AggregateEventsBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function publish(AggregateEvents $events): void
    {
        $this->bus->dispatch($events);
    }
}
