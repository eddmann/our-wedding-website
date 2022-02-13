<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\FoodChoice\FoodChoiceId;
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
            InviteId::generate()->toString(),
            'day',
            'adult',
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
            'day',
            'adult',
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
            'day',
            'adult',
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
}
