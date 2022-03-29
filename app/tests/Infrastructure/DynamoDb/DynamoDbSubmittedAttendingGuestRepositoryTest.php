<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\DynamoDb;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use App\Infrastructure\DynamoDb\DynamoDbSubmittedAttendingGuestRepository;

final class DynamoDbSubmittedAttendingGuestRepositoryTest extends DynamoDbTestCase
{
    private SubmittedAttendingGuestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DynamoDbSubmittedAttendingGuestRepository(
            $this->client,
            $this->projectionTableName
        );
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
