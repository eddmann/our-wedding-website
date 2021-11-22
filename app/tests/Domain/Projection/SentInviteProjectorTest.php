<?php declare(strict_types=1);

namespace App\Tests\Domain\Projection;

use App\Domain\Helpers\{AggregateEvents, AggregateVersion};
use App\Domain\Model\Invite\Events\{InviteWasAuthenticated, InviteWasCreated, InviteWasSubmitted};
use App\Domain\Model\Invite\Guest\{ChosenFoodChoices, GuestId, GuestName, InvitedGuest};
use App\Domain\Model\Invite\{InviteCode, InviteId, InviteType};
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\SentInvite\{SentInvite, SentInviteProjector, SentInviteRepository};
use App\Tests\Doubles\{ChosenFoodChoiceValidatorStub, InMemorySentInviteRepository};
use PHPUnit\Framework\TestCase;

final class SentInviteProjectorTest extends TestCase
{
    private SentInviteProjector $projector;
    private SentInviteRepository $repository;

    protected function setUp(): void
    {
        $this->projector = new SentInviteProjector(
            $this->repository = new InMemorySentInviteRepository()
        );
    }

    public function test_it_adds_an_sent_invite(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    AggregateVersion::zero(),
                    $inviteCode = InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $guest = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            );

        ($this->projector)($events);

        $invite = $this->repository->get($inviteId->toString());

        self::assertEquals(
            new SentInvite(
                $inviteId->toString(),
                $inviteCode->toString(),
                'day',
                [
                    [
                        'id' => $guest->getId()->toString(),
                        'name' => $guest->getName()->toString(),
                        'inviteType' => $guest->getInviteType()->toString(),
                        'guestType' => $guest->getGuestType()->toString(),
                        'hasFoodChoices' => $guest->hasFoodChoices(),
                    ],
                ],
            ),
            $invite
        );
    }

    public function test_it_updates_an_submitted_sent_invite(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $guest = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')), ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [
                        $guest->submit(
                            new ChosenFoodChoiceValidatorStub(),
                            ChosenFoodChoices::fromArray([
                                'starterId' => 'e96ddf7f-0897-48f5-9534-81d596e3542f',
                                'mainId' => '2ff8ec3a-e91b-4734-9a4a-bed588d6894f',
                                'dessertId' => 'd53efc4d-83f3-41c3-9723-93961e245300',
                            ])
                        ),
                    ],
                    [],
                    $submittedAt = new \DateTimeImmutable()
                )
            );

        ($this->projector)($events);

        $invite = $this->repository->get($inviteId->toString());

        self::assertTrue($invite->isSubmitted());
        self::assertEquals($submittedAt, $invite->getSubmittedAt());
    }

    public function test_it_updates_an_authenticated_sent_invite(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasAuthenticated(
                    $inviteId,
                    $version->next(),
                    $authenticatedAt = new \DateTimeImmutable()
                )
            );

        ($this->projector)($events);

        $invite = $this->repository->get($inviteId->toString());

        self::assertEquals($authenticatedAt, $invite->getLastAuthenticatedAt());
    }
}
