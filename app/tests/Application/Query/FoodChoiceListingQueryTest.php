<?php declare(strict_types=1);

namespace App\Tests\Application\Query;

use App\Application\Query\FoodChoiceListingQuery;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Helpers\Projector;
use App\Domain\Model\FoodChoice\Event\FoodChoiceWasCreated;
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\FoodChoice\FoodChoiceName;
use App\Domain\Model\FoodChoice\FoodCourse;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceProjector;
use App\Tests\Doubles\InMemoryAvailableFoodChoiceRepository;
use App\Tests\Doubles\InMemoryEventStore;
use App\Tests\Doubles\InMemoryEventStreamPointerStore;
use PHPUnit\Framework\TestCase;

final class FoodChoiceListingQueryTest extends TestCase
{
    private AvailableFoodChoiceProjector $availableFoodChoiceProjector;
    private FoodChoiceListingQuery $query;

    protected function setUp(): void
    {
        $this->availableFoodChoiceProjector = new AvailableFoodChoiceProjector(
            $availableFoodChoiceRepository = new InMemoryAvailableFoodChoiceRepository()
        );
        $this->query = new FoodChoiceListingQuery($availableFoodChoiceRepository);
    }

    public function test_it_lists_present_food_choices(): void
    {
        $choices = $this->addBaseFoodChoices();

        self::assertEquals([
            [
                'id' => $choices['adultMainId'],
                'name' => 'Adult Main',
                'guestType' => 'adult',
                'course' => 'main',
            ],
            [
                'id' => $choices['childMainId'],
                'name' => 'Child Main',
                'guestType' => 'child',
                'course' => 'main',

            ],
        ], $this->query->query());
    }

    private function addBaseFoodChoices(): array
    {
        $events = AggregateEvents::make()
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
                    $childMainId = FoodChoiceId::generate(),
                    AggregateVersion::zero(),
                    GuestType::Child,
                    FoodCourse::Main,
                    FoodChoiceName::fromString('Child Main'),
                    new \DateTimeImmutable()
                )
            );

        $this->handle($this->availableFoodChoiceProjector, $events);

        return [
            'adultMainId' => $adultMainId->toString(),
            'childMainId' => $childMainId->toString(),
        ];
    }

    private function handle(Projector $projector, AggregateEvents $events): void
    {
        $eventStore = new InMemoryEventStore();
        $eventStore->store($events);

        $projector->handle($eventStore, new InMemoryEventStreamPointerStore());
    }
}
