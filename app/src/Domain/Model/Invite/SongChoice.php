<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

/** @psalm-immutable */
final class SongChoice
{
    private function __construct(private string $artist, private string $track)
    {
        if ($artist === '') {
            throw new \DomainException('Song choice is missing an artist');
        }

        if ($track === '') {
            throw new \DomainException('Song choice is missing an track');
        }
    }

    public function getArtist(): string
    {
        return $this->artist;
    }

    public function getTrack(): string
    {
        return $this->track;
    }

    public static function fromString(string $artist, string $track): self
    {
        return new self($artist, $track);
    }

    public function toArray(): array
    {
        return [
            'artist' => $this->artist,
            'track' => $this->track,
        ];
    }

    public static function fromArray(array $choice): self
    {
        return new self($choice['artist'], $choice['track']);
    }
}
