<?php declare(strict_types=1);

namespace App\Tests\Domain\Projection;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\Invite\Event\InviteWasCreated;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;
use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestProjector;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use App\Tests\Doubles\ChosenFoodChoiceValidatorStub;
use App\Tests\Doubles\InMemorySubmittedAttendingGuestRepository;
use PHPUnit\Framework\TestCase;

final class SubmittedAttendingGuestProjectorTest extends TestCase
{
    private SubmittedAttendingGuestProjector $projection;
    private SubmittedAttendingGuestRepository $repository;

    protected function setUp(): void
    {
        $this->projection = new SubmittedAttendingGuestProjector(
            $this->repository = new InMemorySubmittedAttendingGuestRepository()
        );
    }

    public function test_it_adds_an_attending_guest(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $guest = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [
                        $guest = $guest->submit(
                            new ChosenFoodChoiceValidatorStub(),
                            ChosenFoodChoices::fromArray([
                                'starterId' => 'e96ddf7f-0897-48f5-9534-81d596e3542f',
                                'mainId' => '2ff8ec3a-e91b-4734-9a4a-bed588d6894f',
                                'dessertId' => 'd53efc4d-83f3-41c3-9723-93961e245300',
                            ])
                        ),
                    ],
                    [],
                    new \DateTimeImmutable()
                )
            );

        ($this->projection)($events);

        self::assertEquals(
            new SubmittedAttendingGuest(
                $guest->getId()->toString(),
                $inviteId->toString(),
                $guest->getInviteType()->toString(),
                $guest->getGuestType()->toString(),
                $guest->getName()->toString(),
                $guest->getChosenFoodChoices()->toArray(),
            ),
            $this->repository->get($guest->getId()->toString())
        );
    }
}
