<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\SubmitInvite\{InviteSubmitted, SubmitInviteCommand, SubmitInviteCommandHandler};
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\Invite\Guest\{GuestId, GuestName, InvitedGuest};
use App\Domain\Model\Invite\{Invite, InviteCode, InviteId, InviteRepository, InviteType, SongChoice};
use App\Domain\Model\Shared\GuestType;
use App\Tests\Doubles\{ChosenFoodChoiceValidatorStub, DomainEventBusSpy};

final class SubmitInviteCommandTest extends CommandTestCase
{
    private const NO_SONG_CHOICES = [];
    private const NO_FOOD_CHOICES = [];

    private SubmitInviteCommandHandler $handler;
    private InviteRepository $repository;
    private ChosenFoodChoiceValidatorStub $foodMenuValidator;
    private DomainEventBusSpy $eventBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new SubmitInviteCommandHandler(
            $this->foodMenuValidator = new ChosenFoodChoiceValidatorStub(),
            $this->repository = new InviteRepository($this->eventStore),
            $this->eventBus = new DomainEventBusSpy()
        );
    }

    public function test_successfully_submits_pending_invite_with_all_guests_attending(): void
    {
        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                $child = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child Name')),
                $baby = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [
                $adult->getId()->toString() => self::NO_FOOD_CHOICES,
                $child->getId()->toString() => self::NO_FOOD_CHOICES,
                $baby->getId()->toString() => self::NO_FOOD_CHOICES,
            ],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        self::assertCount(3, $invite->getAttendingGuests());
        self::assertEquals(new InviteSubmitted($id->toString(), [
            ['name' => 'Adult Name', 'attending' => true],
            ['name' => 'Child Name', 'attending' => true],
            ['name' => 'Baby Name', 'attending' => true],
        ]), $this->eventBus->getLastEvent());
    }

    public function test_unable_to_submit_invite_more_than_once(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This invite has already been submitted');

        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
        ($this->handler)($command);
    }

    public function test_successfully_submits_pending_invite_with_single_guest_attending(): void
    {
        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        self::assertCount(1, $invite->getAttendingGuests());
        self::assertEquals(new InviteSubmitted($id->toString(), [
            ['name' => 'Adult Name', 'attending' => true],
            ['name' => 'Child Name', 'attending' => false],
            ['name' => 'Baby Name', 'attending' => false],
        ]), $this->eventBus->getLastEvent());
    }

    public function test_successfully_submits_pending_invite_with_no_guests_attending(): void
    {
        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        self::assertEmpty($invite->getAttendingGuests());
        self::assertEquals(new InviteSubmitted($id->toString(), [
            ['name' => 'Adult Name', 'attending' => false],
            ['name' => 'Child Name', 'attending' => false],
            ['name' => 'Baby Name', 'attending' => false],
        ]), $this->eventBus->getLastEvent());
    }

    public function test_successfully_submits_day_invite_with_guest_food_choices(): void
    {
        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Day,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [
                $adult->getId()->toString() => [
                    'starterId' => $starterId = '98704d73-9bca-40a4-8411-d77e57e91f22',
                    'mainId' => $mainId = '4e8e2055-d557-40fb-aa84-edbf1bdbe9cf',
                    'dessertId' => $dessertId = '5c336bac-fde4-41bb-9cbc-0e9a594e8850',
                    'dietaryRequirements' => 'SAMPLE_DIETARY_REQUIREMENTS',
                ],
            ],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        $chosenFoodChoices = $invite->getAttendingGuests()[0]->getChosenFoodChoices();
        self::assertTrue(FoodChoiceId::fromString($starterId)->equals($chosenFoodChoices->getStarterId()));
        self::assertTrue(FoodChoiceId::fromString($mainId)->equals($chosenFoodChoices->getMainId()));
        self::assertTrue(FoodChoiceId::fromString($dessertId)->equals($chosenFoodChoices->getDessertId()));
        self::assertEquals(new InviteSubmitted($id->toString(), [
            ['name' => 'Adult Name', 'attending' => true],
        ]), $this->eventBus->getLastEvent());
    }

    public function test_fails_to_submit_day_invite_with_empty_required_guest_food_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Adult Name's food choices do not meet the specified requirements");

        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Day,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
    }

    public function test_fails_to_submit_day_invite_with_invalid_guest_food_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Adult Name's food choices do not meet the adult type requirements");

        $this->foodMenuValidator->failing();

        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Day,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [
                $adult->getId()->toString() => [
                    'starterId' => '98704d73-9bca-40a4-8411-d77e57e91f22',
                    'mainId' => '4e8e2055-d557-40fb-aa84-edbf1bdbe9cf',
                    'dessertId' => '5c336bac-fde4-41bb-9cbc-0e9a594e8850',
                    'dietaryRequirements' => 'SAMPLE_DIETARY_REQUIREMENTS',
                ],
            ],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
    }

    public function test_fails_to_submit_evening_invite_with_food_choices_present(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Adult Name's food choices do not meet the specified requirements");

        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [
                $adult->getId()->toString() => [
                    'starterId' => '98704d73-9bca-40a4-8411-d77e57e91f22',
                    'mainId' => '4e8e2055-d557-40fb-aa84-edbf1bdbe9cf',
                    'dessertId' => '5c336bac-fde4-41bb-9cbc-0e9a594e8850',
                    'dietaryRequirements' => 'SAMPLE_DIETARY_REQUIREMENTS',
                ],
            ],
            self::NO_SONG_CHOICES,
        );

        ($this->handler)($command);
    }

    public function test_successfully_submits_invite_with_song_choices(): void
    {
        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [$adult->getId()->toString() => []],
            [
                ['artist' => 'ARTIST_1', 'track' => 'TRACK_1'],
                ['artist' => 'ARTIST_2', 'track' => 'TRACK_2'],
            ]
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertEquals([
            SongChoice::fromString('ARTIST_1', 'TRACK_1'),
            SongChoice::fromString('ARTIST_2', 'TRACK_2'),
        ], $invite->getSongChoices());
    }

    public function test_fails_to_submit_invite_with_too_many_song_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only two song choices allowed per invite');

        $invite = Invite::create(
            $id = InviteId::generate(),
            InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                $adult = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $id->toString(),
            [$adult->getId()->toString() => []],
            [
                ['artist' => 'ARTIST_1', 'track' => 'TRACK_1'],
                ['artist' => 'ARTIST_2', 'track' => 'TRACK_2'],
                ['artist' => 'ARTIST_3', 'track' => 'TRACK_3'],
            ]
        );

        ($this->handler)($command);
    }
}
