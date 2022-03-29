<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\DynamoDb;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use App\Infrastructure\DynamoDb\DynamoDbSubmittedSongChoiceRepository;

final class DynamoDbSubmittedSongChoiceRepositoryTest extends DynamoDbTestCase
{
    private SubmittedSongChoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DynamoDbSubmittedSongChoiceRepository(
            $this->client,
            $this->projectionTableName
        );
    }

    public function test_it_persists_and_hydrates_submitted_song_choice(): void
    {
        $choice = new SubmittedSongChoice(
            'ARTIST_NAME',
            'TRACK_NAME',
            new \DateTimeImmutable()
        );

        $this->repository->store($choice);

        self::assertEquals(
            [$choice],
            $this->repository->all()
        );
    }
}
