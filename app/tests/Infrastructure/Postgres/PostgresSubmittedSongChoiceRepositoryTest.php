<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\Postgres;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use App\Infrastructure\Postgres\PostgresSubmittedSongChoiceRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PostgresSubmittedSongChoiceRepositoryTest extends KernelTestCase
{
    private SubmittedSongChoiceRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->repository = new PostgresSubmittedSongChoiceRepository(
            $this->connection = self::getContainer()->get(Connection::class),
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

        parent::tearDown();
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
