<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Helpers\{AggregateEvents, EventStore, EventStreamPointer};
use App\Domain\Model\Invite\Guest\{GuestId, GuestName, InvitedGuest};
use App\Domain\Model\Invite\{Invite, InviteCode, InviteId, InviteType};
use App\Domain\Model\Shared\GuestType;
use App\Infrastructure\DBALEventStore;
use App\Tests\Doubles\AggregateEventsBusSpy;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALEventStoreTest extends KernelTestCase
{
    private EventStore $eventStore;
    private AggregateEventsBusSpy $aggregateEventsBus;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->eventStore = new DBALEventStore(
            $this->connection = self::getContainer()->get(Connection::class),
            $this->aggregateEventsBus = new AggregateEventsBusSpy()
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

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
        [,$eventsC] = $this->createInvite();

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 1);

        self::assertEquals(EventStreamPointer::fromInt(1), $stream->getNextPointer());
        self::assertEquals($eventsA, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);

        self::assertEquals(EventStreamPointer::fromInt(2), $stream->getNextPointer());
        self::assertEquals($eventsB, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);

        self::assertEquals(EventStreamPointer::fromInt(3), $stream->getNextPointer());
        self::assertEquals($eventsC, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);

        self::assertEquals(EventStreamPointer::fromInt(3), $stream->getNextPointer());
        self::assertCount(0, $stream->getEvents());
    }

    public function test_it_returns_last_event_pointer_if_stream_has_been_exhausted(): void
    {
        $this->createInvite();
        $this->createInvite();
        $this->createInvite();

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 5);

        self::assertEquals(EventStreamPointer::fromInt(3), $stream->getNextPointer());
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
}
