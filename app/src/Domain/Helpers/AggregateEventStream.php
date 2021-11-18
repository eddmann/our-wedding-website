<?php declare(strict_types=1);

namespace App\Domain\Helpers;

final class AggregateEventStream
{
    private EventStreamPointer $next;
    private AggregateEvents $events;

    public function __construct(EventStreamPointer $next, AggregateEvents $events)
    {
        $this->next = $next;
        $this->events = $events;
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
