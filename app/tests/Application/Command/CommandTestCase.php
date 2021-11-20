<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Domain\Helpers\{AggregateEvent, EventStore, EventStreamPointer};
use App\Tests\Doubles\InMemoryEventStore;
use PHPUnit\Framework\TestCase;

abstract class CommandTestCase extends TestCase
{
    protected EventStore $eventStore;
    private bool $skipEventStoreSnapshotAssertion = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStore = new InMemoryEventStore();
    }

    protected function assertPostConditions(): void
    {
        $this->assertEventStoreSnapshotHasNotChanged();
    }

    protected function skipEventStoreSnapshotAssertion(): void
    {
        $this->skipEventStoreSnapshotAssertion = true;
    }

    private function assertEventStoreSnapshotHasNotChanged(): void
    {
        if ($this->skipEventStoreSnapshotAssertion) {
            $this->skipEventStoreSnapshotAssertion = false;

            return;
        }

        $testCase = new \ReflectionClass($this);
        $className = $testCase->getShortName();
        $directoryPath = \dirname($testCase->getFileName());
        $testName = \preg_replace('/[^a-z0-9]+/i', '_', $this->getName());

        $snapshotDirectory = $directoryPath . '/EventStoreSnapshots/' . $className;
        $snapshotFilePath = $snapshotDirectory . '/' . $testName . '.json';

        $events = $this->allStoreEventsAsArray();

        if (\is_file($snapshotFilePath)) {
            $snapshot = \json_decode_array(\file_get_contents($snapshotFilePath));

            try {
                static::assertEquals(
                    $this->ignoreKnownDifferentEventAttributes($snapshot['events']),
                    $this->ignoreKnownDifferentEventAttributes($events),
                    'Final event store stream does not matched expected snapshot'
                );

                return;
            } catch (\Exception $e) {
                if (! ($_ENV['UPDATE_EVENT_STORE_SNAPSHOT_MISMATCHES'] ?? false)) {
                    throw $e;
                }
            }
        }

        if (empty($events)) {
            return;
        }

        if (! \is_dir($snapshotDirectory)) {
            \mkdir($snapshotDirectory, 0o755, true);
        }

        $serializedSnapshot = \json_encode_array([
            'generated' => \datetime_timestamp(new \DateTimeImmutable()),
            'events' => $events,
        ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);

        \file_put_contents($snapshotFilePath, $serializedSnapshot);
    }

    private function ignoreKnownDifferentEventAttributes(array $events): array
    {
        return \array_map(static function (array $event) {
            unset($event['data']['id'], $event['data']['occurredAt']);

            return $event;
        }, [...$events]);
    }

    private function allStoreEventsAsArray(): array
    {
        $events = [];

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 100);
        while (! $stream->getEvents()->isEmpty()) {
            $newEvents = $stream->getEvents()->reduce(
                static fn (array $events, AggregateEvent $event) => [...$events, ['name' => \get_class($event), 'data' => \json_decode_array($event->serialize())]],
                []
            );
            $events = [...$events, ...$newEvents];
            $stream = $this->eventStore->stream($stream->getNextPointer(), 100);
        }

        return $events;
    }
}
