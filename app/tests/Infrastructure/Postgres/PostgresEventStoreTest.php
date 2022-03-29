<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\Postgres;

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
use App\Infrastructure\Postgres\PostgresEventStore;
use App\Tests\Doubles\AggregateEventsBusSpy;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PostgresEventStoreTest extends KernelTestCase
{
    private EventStore $eventStore;
    private AggregateEventsBusSpy $aggregateEventsBus;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->eventStore = new PostgresEventStore(
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

        self::assertEquals($invite, $hydrated);
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
        self::assertTrue($stream->getEvents()->isEmpty());
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
                InvitedGuest::createForInvite(
                    $inviteType,
                    GuestId::generate(),
                    GuestType::Adult,
                    GuestName::fromString('Adult name')
                ),
                InvitedGuest::createForInvite(
                    $inviteType,
                    GuestId::generate(),
                    GuestType::Child,
                    GuestName::fromString('Child name')
                ),
                InvitedGuest::createForInvite(
                    $inviteType,
                    GuestId::generate(),
                    GuestType::Baby,
                    GuestName::fromString('Baby name')
                ),
            ]
        );

        $this->eventStore->store($events = $invite->flushEvents());

        return [$invite, $events];
    }
}
