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
use App\Infrastructure\EventStoreDBEventStore;
use App\Tests\Doubles\AggregateEventsBusSpy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\CurlHttpClient;

final class EventStoreDBEventStoreTest extends KernelTestCase
{
    private EventStore $eventStore;
    private AggregateEventsBusSpy $aggregateEventsBus;

    protected function setUp(): void
    {
        $this->eventStore = new EventStoreDBEventStore(
            'http://eventstore:2113',
            new CurlHttpClient(),
            $this->aggregateEventsBus = new AggregateEventsBusSpy(),
            $prefix = \uniqid('test-') . ':'
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
        [,$inviteA] = $this->createInvite();
        [,$inviteB] = $this->createInvite();
        [,$inviteC] = $this->createInvite();

        $events = $this->eventStore->stream(EventStreamPointer::beginning(), 2);
        self::assertEquals($inviteA->merge($inviteB), $events->getEvents());

        $events = $this->eventStore->stream($events->getNextPointer(), 2);
        self::assertEquals($inviteC, $events->getEvents());

        $events = $this->eventStore->stream($events->getNextPointer(), 2);
        self::assertTrue($events->getEvents()->isEmpty());
    }

    public function test_it_handles_new_events_appearing_within_the_stream(): void
    {
        [,$inviteA] = $this->createInvite();

        $events = $this->eventStore->stream(EventStreamPointer::beginning(), 2);
        self::assertEquals($inviteA, $events->getEvents());

        $events = $this->eventStore->stream($events->getNextPointer(), 2);
        self::assertTrue($events->getEvents()->isEmpty());

        [,$inviteB] = $this->createInvite();

        $events = $this->eventStore->stream($events->getNextPointer(), 2);
        self::assertEquals($inviteB, $events->getEvents());
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
