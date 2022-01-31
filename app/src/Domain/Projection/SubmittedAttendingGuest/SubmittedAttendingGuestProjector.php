<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedAttendingGuest;

use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;

final class SubmittedAttendingGuestProjector extends AggregateEventsSubscriber
{
    public function __construct(private SubmittedAttendingGuestRepository $repository)
    {
    }

    protected function handleInviteWasSubmitted(InviteWasSubmitted $event): void
    {
        foreach ($event->getAttendingGuests() as $guest) {
            $this->repository->store(
                new SubmittedAttendingGuest(
                    $guest->getId()->toString(),
                    $event->getAggregateId()->toString(),
                    $guest->getInviteType()->toString(),
                    $guest->getGuestType()->toString(),
                    $guest->getName()->toString(),
                    $guest->getChosenFoodChoices()->toArray()
                )
            );
        }
    }
}
