<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommand;
use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommandHandler;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use App\Application\Command\CreateInvite\CreateInviteCommandHandler;
use App\Application\Command\SubmitInvite\SubmitInviteCommand;
use App\Application\Command\SubmitInvite\SubmitInviteCommandHandler;
use App\Domain\Event\InviteSubmitted;
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteRepository;
use App\Domain\Model\Invite\SongChoice;
use App\Tests\Doubles\ChosenFoodChoiceValidatorStub;
use App\Tests\Doubles\DomainEventBusDummy;
use App\Tests\Doubles\DomainEventBusSpy;
use App\Tests\Doubles\InviteAuthenticatorDummy;

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
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                    ['type' => 'child', 'name' => 'Child'],
                    ['type' => 'baby', 'name' => 'Baby'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult, $child, $baby] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
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
        self::assertEquals(
            new InviteSubmitted(
                $invite->getAggregateId()->toString(),
                [
                    ['name' => 'Adult', 'attending' => true],
                    ['name' => 'Child', 'attending' => true],
                    ['name' => 'Baby', 'attending' => true],
                ]
            ),
            $this->eventBus->getLastEvent()
        );
    }

    public function test_unable_to_submit_invite_more_than_once(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This invite has already been submitted');

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
        ($this->handler)($command);
    }

    public function test_successfully_submits_pending_invite_with_single_guest_attending(): void
    {
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                    ['type' => 'child', 'name' => 'Child'],
                    ['type' => 'baby', 'name' => 'Baby'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        self::assertCount(1, $invite->getAttendingGuests());
        self::assertEquals(
            new InviteSubmitted(
                $invite->getAggregateId()->toString(),
                [
                    ['name' => 'Adult', 'attending' => true],
                    ['name' => 'Child', 'attending' => false],
                    ['name' => 'Baby', 'attending' => false],
                ]
            ),
            $this->eventBus->getLastEvent()
        );
    }

    public function test_successfully_submits_pending_invite_with_no_guests_attending(): void
    {
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                    ['type' => 'child', 'name' => 'Child'],
                    ['type' => 'baby', 'name' => 'Baby'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertTrue($invite->isSubmitted());
        self::assertEmpty($invite->getAttendingGuests());
        self::assertEquals(
            new InviteSubmitted(
                $invite->getAggregateId()->toString(),
                [
                    ['name' => 'Adult', 'attending' => false],
                    ['name' => 'Child', 'attending' => false],
                    ['name' => 'Baby', 'attending' => false],
                ]
            ),
            $this->eventBus->getLastEvent()
        );
    }

    public function test_successfully_submits_day_invite_with_guest_food_choices(): void
    {
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'day',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
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
        self::assertEquals(
            new InviteSubmitted(
                $invite->getAggregateId()->toString(),
                [
                    ['name' => 'Adult', 'attending' => true],
                ]
            ),
            $this->eventBus->getLastEvent()
        );
    }

    public function test_fails_to_submit_invite_without_authentication(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invite must be authenticated for submission');

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        [$adult] = $invite->getInvitedGuests();

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
    }

    public function test_fails_to_submit_day_invite_with_empty_required_guest_food_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Adult's food choices do not meet the specified requirements");

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'day',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            self::NO_SONG_CHOICES
        );

        ($this->handler)($command);
    }

    public function test_fails_to_submit_day_invite_with_invalid_guest_food_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Adult's food choices do not meet the adult type requirements");

        $this->foodMenuValidator->failing();

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'day',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
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
        $this->expectExceptionMessage("Adult's food choices do not meet the specified requirements");

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
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
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            [
                ['artist' => 'ARTIST_1', 'track' => 'TRACK_1'],
                ['artist' => 'ARTIST_2', 'track' => 'TRACK_2'],
            ]
        );

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertEquals(
            [
                SongChoice::fromString('ARTIST_1', 'TRACK_1'),
                SongChoice::fromString('ARTIST_2', 'TRACK_2'),
            ],
            $invite->getSongChoices()
        );
    }

    public function test_fails_to_submit_invite_with_too_many_song_choices(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only two song choices allowed per invite');

        $invite = $this->createInvite(
            new CreateInviteCommand(
                'evening',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                ]
            )
        );
        $this->authenticateInvite(new AuthenticateInviteCommand($invite->getInviteCode()->toString()));
        [$adult] = $invite->getInvitedGuests();

        $this->repository->store($invite);

        $command = new SubmitInviteCommand(
            $invite->getAggregateId()->toString(),
            [$adult->getId()->toString() => self::NO_FOOD_CHOICES],
            [
                ['artist' => 'ARTIST_1', 'track' => 'TRACK_1'],
                ['artist' => 'ARTIST_2', 'track' => 'TRACK_2'],
                ['artist' => 'ARTIST_3', 'track' => 'TRACK_3'],
            ]
        );

        ($this->handler)($command);
    }

    private function createInvite(CreateInviteCommand $command): Invite
    {
        $handler = new CreateInviteCommandHandler($this->repository, new DomainEventBusDummy());

        $handler($command);

        return $this->repository->get($command->getId());
    }

    private function authenticateInvite(AuthenticateInviteCommand $command): void
    {
        $handler = new AuthenticateInviteCommandHandler(
            $this->repository,
            $this->eventStore,
            new InviteAuthenticatorDummy()
        );

        $handler($command);
    }
}
