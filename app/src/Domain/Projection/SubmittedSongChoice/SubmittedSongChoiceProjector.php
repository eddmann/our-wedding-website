<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedSongChoice;

use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;

final class SubmittedSongChoiceProjector extends AggregateEventsSubscriber
{
    public function __construct(private SubmittedSongChoiceRepository $repository)
    {
    }

    protected function handleInviteWasSubmitted(InviteWasSubmitted $event): void
    {
        foreach ($event->getSongChoices() as $choice) {
            $this->repository->store(
                new SubmittedSongChoice(
                    $choice->getArtist(),
                    $choice->getTrack(),
                    $event->getOccurredAt()
                )
            );
        }
    }
}
