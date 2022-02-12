<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use App\Infrastructure\DynamoDbSubmittedAttendingGuestRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DynamoDbSubmittedAttendingGuestRepositoryTest extends KernelTestCase
{
    private SubmittedAttendingGuestRepository $repository;
    private DynamoDbClient $client;
    private string $tableName;

    protected function setUp(): void
    {
        $this->repository = new DynamoDbSubmittedAttendingGuestRepository(
            $this->client = self::getContainer()->get(DynamoDbClient::class),
            $this->tableName = $this->getContainer()->getParameter('projection_table_name'),
        );
    }

    protected function tearDown(): void
    {
        $this->clearProjectionTable();

        parent::tearDown();
    }

    public function test_it_persists_and_hydrates_attending_guest(): void
    {
        $guest = new SubmittedAttendingGuest(
            $guestId = GuestId::generate()->toString(),
            InviteId::generate()->toString(),
            InviteType::Day->toString(),
            GuestType::Adult->toString(),
            'Guest name',
            [
                'starterId' => FoodChoiceId::generate()->toString(),
                'mainId' => FoodChoiceId::generate()->toString(),
                'dessertId' => FoodChoiceId::generate()->toString(),
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            $guest,
            $this->repository->get($guestId)
        );
    }

    public function test_it_fetches_attending_guests_by_invite_id(): void
    {
        $guest = new SubmittedAttendingGuest(
            GuestId::generate()->toString(),
            $inviteId = InviteId::generate()->toString(),
            InviteType::Day->toString(),
            GuestType::Adult->toString(),
            'Guest name',
            [
                'starterId' => FoodChoiceId::generate()->toString(),
                'mainId' => FoodChoiceId::generate()->toString(),
                'dessertId' => FoodChoiceId::generate()->toString(),
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            [$guest],
            $this->repository->getByInviteId($inviteId)
        );
    }

    public function test_it_fetches_all_attending_guests(): void
    {
        $guest = new SubmittedAttendingGuest(
            GuestId::generate()->toString(),
            InviteId::generate()->toString(),
            InviteType::Day->toString(),
            GuestType::Adult->toString(),
            'Guest name',
            [
                'starterId' => FoodChoiceId::generate()->toString(),
                'mainId' => FoodChoiceId::generate()->toString(),
                'dessertId' => FoodChoiceId::generate()->toString(),
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            [$guest],
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
