<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\DynamoDb;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Infrastructure\DynamoDb\DynamoDbAvailableFoodChoiceRepository;

final class DynamoDbAvailableFoodChoiceRepositoryTest extends DynamoDbTestCase
{
    private AvailableFoodChoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DynamoDbAvailableFoodChoiceRepository(
            $this->client,
            $this->projectionTableName
        );
    }

    public function test_it_persists_and_hydrates_available_food_choice(): void
    {
        $choice = new AvailableFoodChoice(
            $foodChoiceId = FoodChoiceId::generate()->toString(),
            'starter',
            'adult',
            'Starter name'
        );

        $this->repository->store($choice);

        self::assertEquals(
            $choice,
            $this->repository->get($foodChoiceId)
        );
    }

    public function test_it_fetches_food_courses_by_guest_type(): void
    {
        $starter = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'starter', 'adult', 'Starter name');
        $main = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'main', 'adult', 'Main name');
        $dessert = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'dessert', 'adult', 'Dessert name');

        $this->repository->store($starter);
        $this->repository->store($main);
        $this->repository->store($dessert);

        self::assertEquals(
            [
                'starter' => [$starter],
                'main' => [$main],
                'dessert' => [$dessert],
            ],
            $this->repository->getCoursesByGuestType('adult')
        );
    }

    public function test_it_fetches_all_food_choices(): void
    {
        $starter = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'starter', 'adult', 'Starter name');
        $main = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'main', 'adult', 'Main name');
        $dessert = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'dessert', 'adult', 'Dessert name');

        $this->repository->store($starter);
        $this->repository->store($main);
        $this->repository->store($dessert);

        self::assertEquals(
            [$dessert, $main, $starter],
            $this->repository->all()
        );
    }
}
