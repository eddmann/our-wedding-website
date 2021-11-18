<?php declare(strict_types=1);

namespace App\Domain\Helpers;

interface EventStore
{
    public function store(AggregateEvents $events): void;

    public function get(AggregateName $name, AggregateId $id): AggregateEvents;

    public function stream(EventStreamPointer $start, int $limit): AggregateEventStream;
}
