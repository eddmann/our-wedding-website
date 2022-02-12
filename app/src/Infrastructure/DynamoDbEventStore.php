<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\AggregateEvent;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventsBus;
use App\Domain\Helpers\AggregateEventStream;
use App\Domain\Helpers\AggregateId;
use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointer;
use App\Domain\Model\Shared\AggregateEventFactory;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\Select;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbEventStore implements EventStore
{
    private const EVENT_SEQUENCE_PK = 'all';

    public function __construct(
        private DynamoDbClient $client,
        private AggregateEventsBus $eventsBus,
        private string $tableName
    ) {
    }

    public function store(AggregateEvents $events): void
    {
        /** @var AggregateEvent $event */
        foreach ($events as $event) {
            $this->client->putItem(
                new PutItemInput([
                    'TableName' => $this->tableName,
                    'Item' => self::toTableItem($event),
                    'ConditionExpression' => 'attribute_not_exists(AggregateId) AND attribute_not_exists(AggregateVersion)',
                ])
            );
        }

        $this->eventsBus->publish($events);
    }

    public function get(AggregateName $name, AggregateId $id): AggregateEvents
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'Select' => Select::SPECIFIC_ATTRIBUTES,
                'AttributesToGet' => ['EventName', 'EventData'],
                'KeyConditions' => [
                    'AggregateId' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'AggregateId' => [
                                'S' => \sprintf('%s#%s', $name->toString(), $id->toString()),
                            ],
                        ],
                    ],
                ],
            ])
        );

        return \array_reduce(
            \iterator_to_array($result->getIterator()),
            static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['EventName']->getS(), $event['EventData']->getS())),
            AggregateEvents::make()
        );
    }

    public function stream(EventStreamPointer $start, int $limit): AggregateEventStream
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'EventSequence',
                'KeyConditions' => [
                    'EventSequencePartition' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'EventSequencePartition' => ['S' => self::EVENT_SEQUENCE_PK],
                        ],
                    ],
                ],
                'ExclusiveStartKey' => $this->toExclusiveStartKey($start),
                'Limit' => $limit,
            ])
        );

        $events = [...$result->getItems(true)];

        if (empty($events)) {
            return new AggregateEventStream($start, AggregateEvents::make());
        }

        $last = $events[\count($events) - 1];

        return new AggregateEventStream(
            EventStreamPointer::fromString(
                \sprintf(
                    '%s/%s/%s/%s',
                    $last['AggregateId']->getS() ?? '',
                    $last['AggregateVersion']->getS() ?? '',
                    $last['EventSequencePartition']->getS() ?? '',
                    $last['EventSequenceOccurredAt']->getS() ?? '',
                )
            ),
            \array_reduce(
                $events,
                static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['EventName']->getS(), $event['EventData']->getS())),
                AggregateEvents::make()
            )
        );
    }

    private function toExclusiveStartKey(EventStreamPointer $start): ?array
    {
        $offset = $start->toString('');

        if ($offset === '') {
            return null;
        }

        [
            $aggregateId,
            $aggregateVersion,
            $eventSequencePartition,
            $eventSequenceOccurredAt
        ] = \explode('/', $offset, 4);

        return [
            'AggregateId' => ['S' => $aggregateId],
            'AggregateVersion' => ['S' => $aggregateVersion],
            'EventSequencePartition' => ['S' => $eventSequencePartition],
            'EventSequenceOccurredAt' => ['S' => $eventSequenceOccurredAt],
        ];
    }

    private static function toTableItem(AggregateEvent $event): array
    {
        return [
            'AggregateId' => [
                'S' => \sprintf(
                    '%s#%s',
                    $event->getAggregateName()->toString(),
                    $event->getAggregateId()->toString()
                ),
            ],
            'AggregateVersion' => ['S' => (string) $event->getAggregateVersion()->toInt()],
            'EventName' => ['S' => $event->getEventName()],
            'EventData' => ['S' => $event->serialize()],
            'EventSequencePartition' => ['S' => self::EVENT_SEQUENCE_PK],
            'EventSequenceOccurredAt' => ['S' => self::microseconds()],
        ];
    }

    private static function microseconds(): string
    {
        $time = \explode(' ', \microtime());

        return $time[1] . \str_replace('0.', '', $time[0]);
    }
}
