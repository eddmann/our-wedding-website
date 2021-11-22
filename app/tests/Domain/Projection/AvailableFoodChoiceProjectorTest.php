<?php declare(strict_types=1);

namespace App\Tests\Domain\Projection;

use App\Domain\Helpers\{AggregateEvents, AggregateVersion};
use App\Domain\Model\FoodChoice\Events\FoodChoiceWasCreated;
use App\Domain\Model\FoodChoice\{FoodChoiceId, FoodChoiceName, FoodCourse};
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\AvailableFoodChoice\{AvailableFoodChoice, AvailableFoodChoiceProjector, AvailableFoodChoiceRepository};
use App\Tests\Doubles\InMemoryAvailableFoodChoiceRepository;
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

    public function test_it_adds_new_food_choice(): void
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

        ($this->projector)($events);

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
}
