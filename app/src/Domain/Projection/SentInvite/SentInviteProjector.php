<?php declare(strict_types=1);

namespace App\Domain\Projection\SentInvite;

use App\Domain\Helpers\Projector;
use App\Domain\Model\Invite\Event\InviteWasAuthenticated;
use App\Domain\Model\Invite\Event\InviteWasCreated;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;
use App\Domain\Model\Invite\Guest\InvitedGuest;

final class SentInviteProjector extends Projector
{
    public function __construct(private SentInviteRepository $repository)
    {
    }

    public function handleInviteWasAuthenticated(InviteWasAuthenticated $event): void
    {
        $invite = $this->repository->get($event->getAggregateId()->toString());

        $invite->authenticated($event->getOccurredAt());

        $this->repository->store($invite);
    }

    public function reset(): void
    {
    }

    public function getName(): string
    {
        return 'sent_invite';
    }

    protected function handleInviteWasCreated(InviteWasCreated $event): void
    {
        $invite = new SentInvite(
            $event->getAggregateId()->toString(),
            $event->getInviteCode()->toString(),
            $event->getInviteType()->toString(),
            \array_map(static fn (InvitedGuest $guest) => $guest->toArray(), $event->getInvitedGuests())
        );

        $this->repository->store($invite);
    }

    protected function handleInviteWasSubmitted(InviteWasSubmitted $event): void
    {
        $invite = $this->repository->get($event->getAggregateId()->toString());

        $invite->submitted($event->getOccurredAt());

        $this->repository->store($invite);
    }
}
