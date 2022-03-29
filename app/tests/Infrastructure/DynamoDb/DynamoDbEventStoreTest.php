<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\DynamoDb;

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
use App\Infrastructure\DynamoDb\DynamoDbEventStore;
use App\Tests\Doubles\AggregateEventsBusSpy;

final class DynamoDbEventStoreTest extends DynamoDbTestCase
{
    private EventStore $eventStore;
    private AggregateEventsBusSpy $aggregateEventsBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStore = new DynamoDbEventStore(
            $this->client,
            $this->aggregateEventsBus = new AggregateEventsBusSpy(),
            $this->eventStoreTableName
        );
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
        self::assertEquals($eventsA, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);
        self::assertEquals($eventsB, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);
        self::assertEquals($eventsC, $stream->getEvents());

        $stream = $this->eventStore->stream($stream->getNextPointer(), 1);
        self::assertTrue($stream->getEvents()->isEmpty());
    }

    public function test_it_returns_last_event_pointer_if_stream_has_been_exhausted(): void
    {
        $this->createInvite();
        $this->createInvite();
        [$lastInvite] = $this->createInvite();

        $stream = $this->eventStore->stream(EventStreamPointer::beginning(), 5);

        self::assertStringContainsString(
            $lastInvite->getAggregateId()->toString(),
            $stream->getNextPointer()->toString()
        );
    }

    /** @psalm-return array{Invite, AggregateEvents} */
    private function createInvite(): array
    {
        $invite = Invite::create(
            InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Day,
            [
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby name')),
            ]
        );

        $this->eventStore->store($events = $invite->flushEvents());

        return [$invite, $events];
    }
}
