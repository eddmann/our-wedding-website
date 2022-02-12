<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Projection\SentInvite\SentInvite;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use App\Infrastructure\DynamoDbSentInviteRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DynamoDbSentInviteRepositoryTest extends KernelTestCase
{
    private SentInviteRepository $repository;
    private DynamoDbClient $client;
    private string $tableName;

    protected function setUp(): void
    {
        $this->repository = new DynamoDbSentInviteRepository(
            $this->client = self::getContainer()->get(DynamoDbClient::class),
            $this->tableName = $this->getContainer()->getParameter('projection_table_name'),
        );
    }

    protected function tearDown(): void
    {
        $this->clearProjectionTable();

        parent::tearDown();
    }

    public function test_it_persists_and_hydrates_sent_invite(): void
    {
        $invite = new SentInvite(
            $inviteId = InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        self::assertEquals(
            $invite,
            $this->repository->get($inviteId)
        );
    }

    public function test_it_updates_an_existing_sent_invite(): void
    {
        $invite = new SentInvite(
            $inviteId = InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        $invite->submitted(new \DateTimeImmutable());

        $this->repository->store($invite);

        self::assertEquals(
            $invite,
            $this->repository->get($inviteId)
        );
    }

    public function test_it_fetches_all_sent_invites(): void
    {
        $invite = new SentInvite(
            InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        self::assertEquals(
            [$invite],
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
