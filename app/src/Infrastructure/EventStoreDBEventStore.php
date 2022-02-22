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
use Symfony\Component\Uid\UuidV4;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EventStoreDBEventStore implements EventStore
{
    public function __construct(
        private string $url,
        private HttpClientInterface $client,
        private AggregateEventsBus $eventsBus,
        private string $prefix = ''
    ) {
    }

    public function store(AggregateEvents $events): void
    {
        /** @var AggregateEvent[] $eventsAsArray */
        $eventsAsArray = [...$events];

        $streamName = \sprintf(
            '%s%s-%s',
            $this->prefix,
            $eventsAsArray[0]->getAggregateName()->toString(),
            $eventsAsArray[0]->getAggregateId()->toString()
        );

        $this->client->request(
            'POST',
            \sprintf('%s/streams/%s', $this->url, $streamName),
            [
                'headers' => [
                    'Content-Type' => 'application/vnd.eventstore.events+json',
                    'ES-ExpectedVersion' => $eventsAsArray[0]->getAggregateVersion()->toInt() - 1,
                ],
                'body' => \json_encode_array(
                    \array_map(static function (AggregateEvent $event) {
                        return [
                            'eventId' => (string) UuidV4::v4(),
                            'eventType' => $event->getEventName(),
                            'data' => \json_decode_array($event->serialize()),
                        ];
                    }, $eventsAsArray)
                ),
            ]
        );

        $this->eventsBus->publish($events);
    }

    public function get(AggregateName $name, AggregateId $id): AggregateEvents
    {
        $streamName = \sprintf('%s%s-%s', $this->prefix, $name->toString(), $id->toString());

        $response = $this->client->request(
            'GET',
            \sprintf('%s/streams/%s?embed=body', $this->url, $streamName),
            [
                'headers' => [
                    'Accept' => 'application/vnd.eventstore.atom+json',
                ],
            ]
        );

        return \array_reduce(
            \array_reverse($response->toArray(true)['entries']),
            static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['eventType'], $event['data'])),
            AggregateEvents::make()
        );
    }

    public function stream(EventStreamPointer $start, int $limit): AggregateEventStream
    {
        $response = $this->client->request(
            'GET',
            \sprintf(
                '%s/streams/$all/filtered/%s/forward/%s?context=streamid&type=regex&data=%s&embed=body',
                $this->url,
                $start->toString(),
                $limit,
                $this->prefix ? "^{$this->prefix}.*$" : '^[^\$].*$'
            ),
            [
                'headers' => [
                    'Accept' => 'application/vnd.eventstore.atom+json',
                ],
            ]
        );

        $responseData = $response->toArray(true);

        if (empty($responseData['entries'])) {
            return new AggregateEventStream($start, AggregateEvents::make());
        }

        return new AggregateEventStream(
            $this->getNextPointer($responseData),
            \array_reduce(
                \array_reverse($responseData['entries']),
                static fn (AggregateEvents $events, array $event) => $events->add(AggregateEventFactory::fromSerialized($event['eventType'], $event['data'])),
                AggregateEvents::make()
            )
        );
    }

    private function getNextPointer(array $responseData): EventStreamPointer
    {
        foreach ($responseData['links'] as $link) {
            if ($link['relation'] === 'previous') {
                \preg_match('/filtered\/(.+)\/forward/', $link['uri'], $matches);

                return new EventStreamPointer($matches[1]);
            }
        }

        throw new \RuntimeException('Unable to find next pointer');
    }
}
