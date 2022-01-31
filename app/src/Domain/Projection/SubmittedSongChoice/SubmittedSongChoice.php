<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedSongChoice;

final class SubmittedSongChoice
{
    public function __construct(
        private string $artist,
        private string $track,
        private \DateTimeImmutable $submittedAt
    ) {
    }

    public function getArtist(): string
    {
        return $this->artist;
    }

    public function getTrack(): string
    {
        return $this->track;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }
}
