<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedSongChoice;

use App\Domain\Helpers\Projector;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;

final class SubmittedSongChoiceProjector extends Projector
{
    public function __construct(private SubmittedSongChoiceRepository $repository)
    {
    }

    public function reset(): void
    {
    }

    public function getName(): string
    {
        return 'submitted_song_choice';
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
