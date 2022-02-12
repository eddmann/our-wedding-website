<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use App\Infrastructure\DynamoDbSubmittedSongChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DynamoDbSubmittedSongChoiceRepositoryTest extends KernelTestCase
{
    private SubmittedSongChoiceRepository $repository;
    private DynamoDbClient $client;
    private string $tableName;

    protected function setUp(): void
    {
        $this->repository = new DynamoDbSubmittedSongChoiceRepository(
            $this->client = self::getContainer()->get(DynamoDbClient::class),
            $this->tableName = $this->getContainer()->getParameter('projection_table_name'),
        );
    }

    protected function tearDown(): void
    {
        $this->clearProjectionTable();

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

    private function clearProjectionTable(): void
    {
        $result = $this->client->scan(['TableName' => $this->tableName]);

        foreach ($result->getItems() as $item) {
            $this->client->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'PK' => ['S' => $item['PK']->getS()],
                    'SK' => ['S' => $item['SK']->getS()],
                ],
            ]);
        }
    }
}
