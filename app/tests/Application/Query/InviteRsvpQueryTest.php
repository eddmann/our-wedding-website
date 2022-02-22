<?php declare(strict_types=1);

namespace App\Tests\Application\Query;

use App\Application\Query\InviteRsvpQuery;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Helpers\Projector;
use App\Domain\Model\FoodChoice\Event\FoodChoiceWasCreated;
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\FoodChoice\FoodChoiceName;
use App\Domain\Model\FoodChoice\FoodCourse;
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
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceProjector;
use App\Domain\Projection\SentInvite\SentInviteProjector;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestProjector;
use App\Tests\Doubles\ChosenFoodChoiceValidatorStub;
use App\Tests\Doubles\InMemoryAvailableFoodChoiceRepository;
use App\Tests\Doubles\InMemoryEventStore;
use App\Tests\Doubles\InMemoryEventStreamPointerStore;
use App\Tests\Doubles\InMemorySentInviteRepository;
use App\Tests\Doubles\InMemorySubmittedAttendingGuestRepository;
use PHPUnit\Framework\TestCase;

final class InviteRsvpQueryTest extends TestCase
{
    private InviteRsvpQuery $query;
    private SentInviteProjector $sentInviteProjector;
    private AvailableFoodChoiceProjector $foodChoiceProjector;
    private SubmittedAttendingGuestProjector $attendingGuestProjector;

    protected function setUp(): void
    {
        $this->sentInviteProjector = new SentInviteProjector(
            $sentInviteRepository = new InMemorySentInviteRepository()
        );
        $this->foodChoiceProjector = new AvailableFoodChoiceProjector(
            $foodChoiceRepository = new InMemoryAvailableFoodChoiceRepository()
        );
        $this->attendingGuestProjector = new SubmittedAttendingGuestProjector(
            $attendingGuestRepository = new InMemorySubmittedAttendingGuestRepository()
        );
        $this->query = new InviteRsvpQuery(
            $sentInviteRepository,
            $foodChoiceRepository,
            $attendingGuestRepository
        );
    }

    public function test_it_returns_pending_day_invite(): void
    {
        $choices = $this->addBaseFoodChoices();
        $invite = $this->createPendingWeddingInvite();

        self::assertEquals([
            'status' => 'pending',
            'type' => 'day',
            'guests' => [
                [
                    'id' => $invite['adultId'],
                    'name' => 'Adult name',
                    'foodChoices' => [
                        'starter' => [['id' => $choices['adultStarterId'], 'name' => 'Adult Starter']],
                        'main' => [['id' => $choices['adultMainId'], 'name' => 'Adult Main']],
                        'dessert' => [['id' => $choices['adultDessertId'], 'name' => 'Adult Dessert']],
                    ],
                ],
                [
                    'id' => $invite['childId'],
                    'name' => 'Child name',
                    'foodChoices' => [
                        'starter' => [['id' => $choices['childStarterId'], 'name' => 'Child Starter']],
                        'main' => [['id' => $choices['childMainId'], 'name' => 'Child Main']],
                        'dessert' => [['id' => $choices['childDessertId'], 'name' => 'Child Dessert']],
                    ],
                ],
                [
                    'id' => $invite['babyId'],
                    'name' => 'Baby name',
                    'foodChoices' => [],
                ],
            ],
        ], $this->query->query($invite['inviteId']));
    }

    public function test_it_returns_pending_evening_invite(): void
    {
        $invite = $this->createPendingEveningInvite();

        self::assertEquals([
            'status' => 'pending',
            'type' => 'evening',
            'guests' => [
                [
                    'id' => $invite['adultId'],
                    'name' => 'Adult name',
                    'foodChoices' => [],
                ],
                [
                    'id' => $invite['childId'],
                    'name' => 'Child name',
                    'foodChoices' => [],
                ],
                [
                    'id' => $invite['babyId'],
                    'name' => 'Baby name',
                    'foodChoices' => [],
                ],
            ],
        ], $this->query->query($invite['inviteId']));
    }

    public function test_it_returns_submitted_day_invite(): void
    {
        $choices = $this->addBaseFoodChoices();
        $invite = $this->createSubmittedWeddingInvite($choices);

        self::assertEquals([
            'status' => 'submitted',
            'type' => 'day',
            'guests' => [
                [
                    'id' => $invite['adultId'],
                    'name' => 'Adult name',
                    'chosenFoodChoices' => ['Adult Starter', 'Adult Main', 'Adult Dessert'],
                ],
                [
                    'id' => $invite['childId'],
                    'name' => 'Child name',
                    'chosenFoodChoices' => ['Child Starter', 'Child Main', 'Child Dessert'],
                ],
                [
                    'id' => $invite['babyId'],
                    'name' => 'Baby name',
                    'chosenFoodChoices' => [],
                ],
            ],
        ], $this->query->query($invite['inviteId']));
    }

    public function test_it_returns_submitted_evening_invite(): void
    {
        $invite = $this->createSubmittedEveningInvite();

        self::assertEquals([
            'status' => 'submitted',
            'type' => 'evening',
            'guests' => [
                [
                    'id' => $invite['adultId'],
                    'name' => 'Adult name',
                    'chosenFoodChoices' => [],
                ],
                [
                    'id' => $invite['childId'],
                    'name' => 'Child name',
                    'chosenFoodChoices' => [],
                ],
                [
                    'id' => $invite['babyId'],
                    'name' => 'Baby name',
                    'chosenFoodChoices' => [],
                ],
            ],
        ], $this->query->query($invite['inviteId']));
    }

    private function createPendingWeddingInvite(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $adult = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Adult,
                            GuestName::fromString('Adult name')
                        ),
                        $child = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Child,
                            GuestName::fromString('Child name')
                        ),
                        $baby = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Baby,
                            GuestName::fromString('Baby name')
                        ),
                    ],
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->sentInviteProjector, $events);
        $this->handle($this->attendingGuestProjector, $events);

        return [
            'inviteId' => $inviteId->toString(),
            'adultId' => $adult->getId()->toString(),
            'childId' => $child->getId()->toString(),
            'babyId' => $baby->getId()->toString(),
        ];
    }

    private function createPendingEveningInvite(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Evening,
                    [
                        $adult = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Adult,
                            GuestName::fromString('Adult name')
                        ),
                        $child = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Child,
                            GuestName::fromString('Child name')
                        ),
                        $baby = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Baby,
                            GuestName::fromString('Baby name')
                        ),
                    ],
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->sentInviteProjector, $events);
        $this->handle($this->attendingGuestProjector, $events);

        return [
            'inviteId' => $inviteId->toString(),
            'adultId' => $adult->getId()->toString(),
            'childId' => $child->getId()->toString(),
            'babyId' => $baby->getId()->toString(),
        ];
    }

    private function createSubmittedWeddingInvite(array $choices): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $adult = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Adult,
                            GuestName::fromString('Adult name')
                        ),
                        $child = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Child,
                            GuestName::fromString('Child name')
                        ),
                        $baby = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Baby,
                            GuestName::fromString('Baby name')
                        ),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [
                        $adult->submit(
                            new ChosenFoodChoiceValidatorStub(),
                            ChosenFoodChoices::fromArray([
                                'starterId' => $choices['adultStarterId'],
                                'mainId' => $choices['adultMainId'],
                                'dessertId' => $choices['adultDessertId'],
                            ])
                        ),
                        $child->submit(
                            new ChosenFoodChoiceValidatorStub(),
                            ChosenFoodChoices::fromArray([
                                'starterId' => $choices['childStarterId'],
                                'mainId' => $choices['childMainId'],
                                'dessertId' => $choices['childDessertId'],
                            ])
                        ),
                        $baby->submit(new ChosenFoodChoiceValidatorStub(), ChosenFoodChoices::none()),
                    ],
                    [],
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->sentInviteProjector, $events);
        $this->handle($this->attendingGuestProjector, $events);

        return [
            'inviteId' => $inviteId->toString(),
            'adultId' => $adult->getId()->toString(),
            'childId' => $child->getId()->toString(),
            'babyId' => $baby->getId()->toString(),
        ];
    }

    private function createSubmittedEveningInvite(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Evening,
                    [
                        $adult = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Adult,
                            GuestName::fromString('Adult name')
                        ),
                        $child = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Child,
                            GuestName::fromString('Child name')
                        ),
                        $baby = InvitedGuest::createForInvite(
                            $inviteType,
                            GuestId::generate(),
                            GuestType::Baby,
                            GuestName::fromString('Baby name')
                        ),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [
                        $adult->submit(new ChosenFoodChoiceValidatorStub(), ChosenFoodChoices::none()),
                        $child->submit(new ChosenFoodChoiceValidatorStub(), ChosenFoodChoices::none()),
                        $baby->submit(new ChosenFoodChoiceValidatorStub(), ChosenFoodChoices::none()),
                    ],
                    [],
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->sentInviteProjector, $events);
        $this->handle($this->attendingGuestProjector, $events);

        return [
            'inviteId' => $inviteId->toString(),
            'adultId' => $adult->getId()->toString(),
            'childId' => $child->getId()->toString(),
            'babyId' => $baby->getId()->toString(),
        ];
    }

    private function addBaseFoodChoices(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new FoodChoiceWasCreated(
                    $adultStarterId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Adult,
                    FoodCourse::Starter,
                    FoodChoiceName::fromString('Adult Starter'),
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new FoodChoiceWasCreated(
                    $adultMainId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Adult,
                    FoodCourse::Main,
                    FoodChoiceName::fromString('Adult Main'),
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new FoodChoiceWasCreated(
                    $adultDessertId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Adult,
                    FoodCourse::Dessert,
                    FoodChoiceName::fromString('Adult Dessert'),
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new FoodChoiceWasCreated(
                    $childStarterId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Child,
                    FoodCourse::Starter,
                    FoodChoiceName::fromString('Child Starter'),
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new FoodChoiceWasCreated(
                    $childMainId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Child,
                    FoodCourse::Main,
                    FoodChoiceName::fromString('Child Main'),
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new FoodChoiceWasCreated(
                    $childDessertId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Child,
                    FoodCourse::Dessert,
                    FoodChoiceName::fromString('Child Dessert'),
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->foodChoiceProjector, $events);

        return [
            'adultStarterId' => $adultStarterId->toString(),
            'adultMainId' => $adultMainId->toString(),
            'adultDessertId' => $adultDessertId->toString(),
            'childStarterId' => $childStarterId->toString(),
            'childMainId' => $childMainId->toString(),
            'childDessertId' => $childDessertId->toString(),
        ];
    }

    private function handle(Projector $projector, AggregateEvents $events): void
    {
        $eventStore = new InMemoryEventStore();
        $eventStore->store($events);

        $projector->handle($eventStore, new InMemoryEventStreamPointerStore());
    }
}
