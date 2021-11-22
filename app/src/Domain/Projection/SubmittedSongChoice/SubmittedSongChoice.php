<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedSongChoice;

final class SubmittedSongChoice
{
    private string $artist;
    private string $track;
    private \DateTimeImmutable $submittedAt;

    public function __construct(string $artist, string $track, \DateTimeImmutable $submittedAt)
    {
        $this->artist = $artist;
        $this->track = $track;
        $this->submittedAt = $submittedAt;
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
