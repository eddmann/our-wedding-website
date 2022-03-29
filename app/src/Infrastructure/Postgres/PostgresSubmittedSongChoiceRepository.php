<?php declare(strict_types=1);

namespace App\Infrastructure\Postgres;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use Doctrine\DBAL\Connection;

final class PostgresSubmittedSongChoiceRepository implements SubmittedSongChoiceRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function store(SubmittedSongChoice $choice): void
    {
        $this->connection->executeStatement(
            'INSERT INTO submitted_song_choice_projection VALUES (:artist, :track, :submittedAt)',
            [
                'artist' => $choice->getArtist(),
                'track' => $choice->getTrack(),
                'submittedAt' => $choice->getSubmittedAt()->format('Y-m-d H:i:s.u O'),
            ]
        );
    }

    public function all(): array
    {
        $result = $this->connection->executeQuery('SELECT * FROM submitted_song_choice_projection');

        return \array_map(
            static fn (array $row): SubmittedSongChoice => new SubmittedSongChoice($row['artist'], $row['track'], new \DateTimeImmutable($row['submitted_at'])),
            $result->fetchAllAssociative()
        );
    }
}
