<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use App\Infrastructure\DBALSubmittedAttendingGuestRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALSubmittedAttendingGuestRepositoryTest extends KernelTestCase
{
    private SubmittedAttendingGuestRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->repository = new DBALSubmittedAttendingGuestRepository(
            $this->connection = self::getContainer()->get(Connection::class),
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

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
}
