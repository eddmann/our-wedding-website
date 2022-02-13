<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointer;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;
use App\Infrastructure\DynamoDbEventStore;
use App\Tests\Doubles\AggregateEventsBusSpy;
use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DynamoDbEventStoreTest extends KernelTestCase
{
    private EventStore $eventStore;
    private AggregateEventsBusSpy $aggregateEventsBus;
    private DynamoDbClient $client;
    private string $tableName;

    protected function setUp(): void
    {
        $this->eventStore = new DynamoDbEventStore(
            $this->client = self::getContainer()->get(DynamoDbClient::class),
            $this->aggregateEventsBus = new AggregateEventsBusSpy(),
            $this->tableName = $this->getContainer()->getParameter('event_table_name'),
        );
    }

    protected function tearDown(): void
    {
        $this->clearEventTable();

        parent::tearDown();
    }

    public function test_it_persists_and_hydrates_aggregate(): void
    {
        [$invite] = $this->createInvite();

        $hydrated = Invite::buildFrom(
            $this->eventStore->get($invite->getAggregateName(), $invite->getAggregateId())
        );

        self::assertTrue($invite->getAggregateName()->equals($hydrated->getAggregateName()));
        self::assertTrue($invite->getAggregateVersion()->equals($hydrated->getAggregateVersion()));
        self::assertTrue($invite->getAggregateId()->equals($hydrated->getAggregateId()));
        self::assertTrue($invite->getInviteType()->equals($hydrated->getInviteType()));
        self::assertTrue($invite->getInviteCode()->equals($hydrated->getInviteCode()));
        self::assertEquals($invite->getInvitedGuests(), $hydrated->getInvitedGuests());
    }

    public function test_it_publishes_stored_events_to_the_bus(): void
    {
        [,$events] = $this->createInvite();

        self::assertEquals($events, $this->aggregateEventsBus->getLastEvents());
    }

    public function test_it_streams_events_with_pointer_capabilities(): void
    {
        [,$eventsA] = $this->createInvite();
        [,$eventsB] = $this->createInvite();
        [$lastInvite,$eventsC] = $this->createInvite();

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 3);

        self::assertEquals(
            $eventsA->merge($eventsB)->merge($eventsC),
            $stream->getEvents()
        );

        self::assertStringContainsString(
            $lastInvite->getAggregateId()->toString(),
            $stream->getNextPointer()->toString('')
        );
    }

    public function test_it_returns_last_event_pointer_if_stream_has_been_exhausted(): void
    {
        $this->createInvite();
        $this->createInvite();
        [$lastInvite] = $this->createInvite();

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 5);

        self::assertStringContainsString($lastInvite->getAggregateId()->toString(), $stream->getNextPointer()->toString(''));
    }

    /** @psalm-return array{Invite, AggregateEvents} */
    private function createInvite(): array
    {
        $invite = Invite::create(
            InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Day,
            [
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby Name')),
            ]
        );

        $this->eventStore->store($events = $invite->flushEvents());

        return [$invite, $events];
    }

    private function clearEventTable(): void
    {
        $result = $this->client->scan(['TableName' => $this->tableName]);

        foreach ($result->getItems() as $item) {
            $this->client->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'AggregateId' => ['S' => $item['AggregateId']->getS()],
                    'AggregateVersion' => ['S' => $item['AggregateVersion']->getS()],
                ],
            ]);
        }
    }
}
