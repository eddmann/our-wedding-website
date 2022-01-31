<?php declare(strict_types=1);

namespace App\Domain\Helpers;

final class AggregateEventStream
{
    public function __construct(
        private EventStreamPointer $next,
        private AggregateEvents $events
    ) {
    }

    public function getNextPointer(): EventStreamPointer
    {
        return $this->next;
    }

    public function getEvents(): AggregateEvents
    {
        return $this->events;
    }
}
