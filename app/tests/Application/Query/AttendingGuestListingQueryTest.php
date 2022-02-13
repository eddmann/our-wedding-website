<?php declare(strict_types=1);

namespace App\Tests\Application\Query;

use App\Application\Query\AttendingGuestListingQuery;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
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
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestProjector;
use App\Tests\Doubles\ChosenFoodChoiceValidatorStub;
use App\Tests\Doubles\InMemoryAvailableFoodChoiceRepository;
use App\Tests\Doubles\InMemorySubmittedAttendingGuestRepository;
use PHPUnit\Framework\TestCase;

final class AttendingGuestListingQueryTest extends TestCase
{
    private AttendingGuestListingQuery $query;
    private SubmittedAttendingGuestProjector $attendingGuestProjector;
    private AvailableFoodChoiceProjector $foodChoiceProjector;

    protected function setUp(): void
    {
        $this->attendingGuestProjector = new SubmittedAttendingGuestProjector(
            $attendingGuestRepository = new InMemorySubmittedAttendingGuestRepository()
        );
        $this->foodChoiceProjector = new AvailableFoodChoiceProjector(
            $foodChoiceRepository = new InMemoryAvailableFoodChoiceRepository()
        );
        $this->query = new AttendingGuestListingQuery($attendingGuestRepository, $foodChoiceRepository);
    }

    public function test_it_lists_attending_day_guests(): void
    {
        $choices = $this->addBaseFoodChoices();
        $invite = $this->createSubmittedWeddingInvite($choices);

        self::assertEquals([
            [
                'id' => $invite['adultId'],
                'inviteType' => 'day',
                'guestType' => 'adult',
                'name' => 'Adult name',
                'chosenFoodChoices' => [
                    'Adult Starter',
                    'Adult Main',
                    'Adult Dessert',
                ],
                'dietaryRequirements' => 'SAMPLE_DIETARY_REQUIREMENT',
            ],
            [
                'id' => $invite['childId'],
                'inviteType' => 'day',
                'guestType' => 'child',
                'name' => 'Child name',
                'chosenFoodChoices' => [
                    'Child Starter',
                    'Child Main',
                    'Child Dessert',
                ],
                'dietaryRequirements' => '',
            ],
            [
                'id' => $invite['babyId'],
                'inviteType' => 'day',
                'guestType' => 'baby',
                'name' => 'Baby name',
                'chosenFoodChoices' => [],
                'dietaryRequirements' => '',
            ],
        ], $this->query->query());
    }

    public function test_it_lists_attending_evening_guests(): void
    {
        $invite = $this->createSubmittedEveningInvite();

        self::assertEquals([
            [
                'id' => $invite['adultId'],
                'inviteType' => 'evening',
                'guestType' => 'adult',
                'name' => 'Adult name',
                'chosenFoodChoices' => [],
                'dietaryRequirements' => '',
            ],
            [
                'id' => $invite['childId'],
                'inviteType' => 'evening',
                'guestType' => 'child',
                'name' => 'Child name',
                'chosenFoodChoices' => [],
                'dietaryRequirements' => '',
            ],
            [
                'id' => $invite['babyId'],
                'inviteType' => 'evening',
                'guestType' => 'baby',
                'name' => 'Baby name',
                'chosenFoodChoices' => [],
                'dietaryRequirements' => '',
            ],
        ], $this->query->query());
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
                                'dietaryRequirements' => 'SAMPLE_DIETARY_REQUIREMENT',
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

        ($this->attendingGuestProjector)($events);

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

        ($this->attendingGuestProjector)($events);

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

        ($this->foodChoiceProjector)($events);

        return [
            'adultStarterId' => $adultStarterId->toString(),
            'adultMainId' => $adultMainId->toString(),
            'adultDessertId' => $adultDessertId->toString(),
            'childStarterId' => $childStarterId->toString(),
            'childMainId' => $childMainId->toString(),
            'childDessertId' => $childDessertId->toString(),
        ];
    }
}
