<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Application\Command\AuthenticateInvite\InviteCodeNotFound;
use App\Application\Command\AuthenticateInvite\InviteCodeToIdFinder;
use App\Domain\Helpers\AggregateEvent;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventStream;
use App\Domain\Helpers\AggregateId;
use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointer;
use App\Domain\Model\Invite\Event\InviteWasCreated;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Shared\AggregateEventFactory;

final class InMemoryEventStore implements EventStore, InviteCodeToIdFinder
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
        $offset = (int) $start->toString('0');
        $stream = \array_slice(\iterator_to_array($this->events), $offset, $limit);

        return new AggregateEventStream(
            EventStreamPointer::fromString((string) ($offset + \count($stream))),
            \array_reduce(
                $stream,
                fn (AggregateEvents $events, AggregateEvent $event) => $events->add($this->toSerializedAndBack($event)),
                AggregateEvents::make()
            )
        );
    }

    public function find(InviteCode $code): InviteId
    {
        foreach ($this->events as $event) {
            if ($event instanceof InviteWasCreated) {
                if ($event->getInviteCode()->equals($code)) {
                    return $event->getAggregateId();
                }
            }
        }

        throw new InviteCodeNotFound($code);
    }

    /**
     * This tests the (de)serialisation logic in-place, which is commonly
     * only exercised in the integration persistence layer.
     */
    private function toSerializedAndBack(AggregateEvent $event): AggregateEvent
    {
        return AggregateEventFactory::fromSerialized($event->getEventName(), $event->serialize());
    }
}
