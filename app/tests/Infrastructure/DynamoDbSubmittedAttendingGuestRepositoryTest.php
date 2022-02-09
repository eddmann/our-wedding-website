<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\InviteId;
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
            $inviteId = InviteId::generate()->toString(),
            'day',
            'adult',
            'Guest',
            [
                'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            new SubmittedAttendingGuest(
                $guestId,
                $inviteId,
                'day',
                'adult',
                'Guest',
                [
                    'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                    'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                    'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
                ]
            ),
            $this->repository->get($guestId)
        );
    }

    public function test_it_fetches_attending_guests_by_invite_id(): void
    {
        $guest = new SubmittedAttendingGuest(
            $guestId = GuestId::generate()->toString(),
            $inviteId = InviteId::generate()->toString(),
            'day',
            'adult',
            'Guest',
            [
                'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            [
                new SubmittedAttendingGuest(
                    $guestId,
                    $inviteId,
                    'day',
                    'adult',
                    'Guest',
                    [
                        'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                        'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                        'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
                    ]
                ),
            ],
            $this->repository->getByInviteId($inviteId)
        );
    }

    public function test_it_fetches_all_attending_guests(): void
    {
        $guest = new SubmittedAttendingGuest(
            $guestId = GuestId::generate()->toString(),
            $inviteId = InviteId::generate()->toString(),
            'day',
            'adult',
            'Guest',
            [
                'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
            ]
        );

        $this->repository->store($guest);

        self::assertEquals(
            [
                new SubmittedAttendingGuest(
                    $guestId,
                    $inviteId,
                    'day',
                    'adult',
                    'Guest',
                    [
                        'starterId' => 'd2322033-d950-41f0-9b78-e8b1b25eef81',
                        'mainId' => 'c537b986-b33a-4757-acbc-decb799c82dc',
                        'dessertId' => 'c8977aef-4317-419a-8645-5a46bcdc55c6',
                    ]
                ),
            ],
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
