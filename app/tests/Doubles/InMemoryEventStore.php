<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\{AggregateEvent, AggregateEventStream, AggregateEvents, AggregateId, AggregateName, EventStreamPointer};
use App\Tests\SerializableEventStore;

final class InMemoryEventStore implements SerializableEventStore
{
    private AggregateEvents $events;

    public function __construct()
    {
        $this->events = AggregateEvents::make();
    }

    public function store(AggregateEvents $events): void
    {
        $this->events = $events->reduce(
            fn (AggregateEvents $events, AggregateEvent $event) => $events->add($this->toSerializedAndBack($event)),
            $this->events
        );
    }

    public function get(AggregateName $name, AggregateId $id): AggregateEvents
    {
        return $this->events->reduce(
            fn (AggregateEvents $events, AggregateEvent $event) => $event->getAggregateName()->equals($name) && $event->getAggregateId()->equals($id)
                ? $events->add($this->toSerializedAndBack($event))
                : $events,
            AggregateEvents::make()
        );
    }

    public function stream(EventStreamPointer $start, int $limit): AggregateEventStream
    {
        $stream = \array_slice(\iterator_to_array($this->events), $start->toInt(), $limit);

        return new AggregateEventStream(
            EventStreamPointer::fromInt($start->toInt() + \count($stream)),
            \array_reduce(
                $stream,
                static fn (AggregateEvents $events, AggregateEvent $event) => $events->add($event),
                AggregateEvents::make()
            )
        );
    }

    public function toArray(): array
    {
        return $this->events->reduce(
            static fn (array $events, AggregateEvent $event)
                => [...$events, ['name' => \get_class($event), 'data' => \json_decode_array($event->serialize())]],
            []
        );
    }

    /**
     * This tests the (de)serialisation logic in-place, which is commonly
     * only exercised in the integration persistence layer.
     */
    private function toSerializedAndBack(AggregateEvent $event): AggregateEvent
    {
        return $event::deserialize($event->serialize());
    }
}
