<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

use App\Domain\Helpers\{AggregateName, EventStore};

final class InviteRepository
{
    public function __construct(private EventStore $eventStore)
    {
    }

    public function store(Invite $invite): void
    {
        $this->eventStore->store($invite->flushEvents());
    }

    /** @throws InviteNotFound */
    public function get(InviteId $id): Invite
    {
        $events = $this->eventStore->get(AggregateName::fromString(Invite::AGGREGATE_NAME), $id);

        if ($events->isEmpty()) {
            throw new InviteNotFound($id);
        }

        return Invite::buildFrom($events);
    }
}
