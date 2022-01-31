<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventsBus;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyAggregateEventsBus implements AggregateEventsBus
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function publish(AggregateEvents $events): void
    {
        $this->bus->dispatch($events);
    }
}
