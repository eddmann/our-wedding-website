<?php declare(strict_types=1);

namespace App\Tests\Domain\Projection;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\FoodChoice\Event\FoodChoiceWasCreated;
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\FoodChoice\FoodChoiceName;
use App\Domain\Model\FoodChoice\FoodCourse;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceProjector;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Tests\Doubles\InMemoryAvailableFoodChoiceRepository;
use App\Tests\Doubles\InMemoryEventStore;
use App\Tests\Doubles\InMemoryEventStreamPointerStore;
use PHPUnit\Framework\TestCase;

final class AvailableFoodChoiceProjectorTest extends TestCase
{
    private AvailableFoodChoiceProjector $projector;
    private AvailableFoodChoiceRepository $repository;

    protected function setUp(): void
    {
        $this->projector = new AvailableFoodChoiceProjector(
            $this->repository = new InMemoryAvailableFoodChoiceRepository()
        );
    }

    public function test_it_adds_a_new_food_choice(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new FoodChoiceWasCreated(
                    FoodChoiceId::fromString($id = '4f66b30b-afce-4204-9710-292921a94705'),
                    AggregateVersion::zero(),
                    GuestType::Adult,
                    FoodCourse::Starter,
                    FoodChoiceName::fromString('FOOD_CHOICE_NAME'),
                    new \DateTimeImmutable()
                )
            );

        $this->handle($events);

        self::assertEquals(
            new AvailableFoodChoice(
                $id,
                'starter',
                'adult',
                'FOOD_CHOICE_NAME'
            ),
            $this->repository->get($id)
        );
    }

    private function handle(AggregateEvents $events): void
    {
        $eventStore = new InMemoryEventStore();
        $eventStore->store($events);

        $this->projector->handle($eventStore, new InMemoryEventStreamPointerStore());
    }
}
