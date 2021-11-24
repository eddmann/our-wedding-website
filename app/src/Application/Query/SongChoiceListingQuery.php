<?php declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;

final class SongChoiceListingQuery
{
    public function __construct(private SubmittedSongChoiceRepository $songChoiceReadModel)
    {
    }

    public function query(): array
    {
        return \array_map(
            static fn (SubmittedSongChoice $choice) => [
                'artist' => $choice->getArtist(),
                'track' => $choice->getTrack(),
                'submittedAt' => $choice->getSubmittedAt()->format('Y-m-d H:i:s'),
            ],
            $this->songChoiceReadModel->all()
        );
    }
}
