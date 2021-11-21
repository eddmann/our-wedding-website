<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\{AggregateEvent, AggregateEventStream, AggregateEvents, AggregateEventsBus, AggregateId, AggregateName, EventStore, EventStreamPointer};
use App\Domain\Model\Shared\AggregateEventFactory;
use Doctrine\DBAL\Connection;

final class DBALEventStore implements EventStore
{
    public function __construct(
        private Connection $connection,
        private AggregateEventsBus $eventsBus
    ) {
    }

    public function store(AggregateEvents $events): void
    {
        $this->connection->beginTransaction();

        $statement = $this->connection->prepare('
            INSERT INTO event_store (aggregate_name, aggregate_id, aggregate_version, event_name, event_data)
            VALUES (:name, :id, :version, :event, :data)
        ');

        /** @var AggregateEvent $event */
        foreach ($events as $event) {
            $statement->executeStatement([
                'name' => $event->getAggregateName()->toString(),
                'id' => $event->getAggregateId()->toString(),
                'version' => $event->getAggregateVersion()->toInt(),
                'event' => $event->getEventName(),
                'data' => $event->serialize(),
            ]);
        }

        $this->eventsBus->publish($events);

        $this->connection->commit();
    }

    public function get(AggregateName $name, AggregateId $id): AggregateEvents
    {
        $statement = $this->connection->prepare('
            SELECT event_name AS name, event_data AS data
            FROM event_store
            WHERE aggregate_name = :name
            AND aggregate_id = :id
            ORDER BY aggregate_version ASC
        ');

        $result = $statement->executeQuery([
            'name' => $name->toString(),
            'id' => $id->toString(),
        ]);

        return \array_reduce(
            $result->fetchAllAssociative(),
            static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['name'], $event['data'])),
            AggregateEvents::make()
        );
    }

    public function stream(EventStreamPointer $start, int $limit): AggregateEventStream
    {
        $statement = $this->connection->prepare('
            SELECT event_name AS name, event_data AS data
            FROM event_store
            ORDER BY id ASC
            LIMIT :limit
            OFFSET :offset
        ');

        $result = $statement->executeQuery([
            'limit' => $limit,
            'offset' => $start->toInt(),
        ]);

        return new AggregateEventStream(
            EventStreamPointer::fromInt($start->toInt() + $result->rowCount()),
            \array_reduce(
                $result->fetchAllAssociative(),
                static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['name'], $event['data'])),
                AggregateEvents::make()
            )
        );
    }
}
