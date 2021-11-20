<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\CreateFoodChoice\{CreateFoodChoiceCommand, CreateFoodChoiceCommandHandler};
use App\Domain\Model\FoodChoice\{FoodChoiceName, FoodChoiceRepository, FoodCourse};
use App\Domain\Model\Shared\GuestType;
use App\Tests\CommandTestCase;

final class CreateFoodChoiceCommandTest extends CommandTestCase
{
    private CreateFoodChoiceCommandHandler $handler;
    private FoodChoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new CreateFoodChoiceCommandHandler(
            $this->repository = new FoodChoiceRepository($this->eventStore)
        );
    }

    public function test_should_create_food_choice(): void
    {
        $command = new CreateFoodChoiceCommand('adult', 'starter', 'Name');

        ($this->handler)($command);

        $choice = $this->repository->get($command->getId());

        self::assertTrue(FoodChoiceName::fromString('Name')->equals($choice->getName()));
        self::assertTrue(FoodCourse::Starter->equals($choice->getCourse()));
        self::assertTrue(GuestType::Adult->equals($choice->getGuestType()));
    }

    public function test_fails_to_create_food_choice_with_invalid_guest_type(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Invalid guest type 'INVALID' supplied");

        new CreateFoodChoiceCommand('INVALID', 'starter', 'Name');
    }

    public function test_fails_to_create_food_choice_with_invalid_course(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Invalid food course 'INVALID' supplied");

        new CreateFoodChoiceCommand('adult', 'INVALID', 'Name');
    }

    public function test_fails_to_create_food_choice_with_empty_name(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Food choices must have a name');

        new CreateFoodChoiceCommand('adult', 'starter', '');
    }
}
