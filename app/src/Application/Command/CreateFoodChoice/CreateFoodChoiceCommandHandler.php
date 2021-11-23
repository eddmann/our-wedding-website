<?php declare(strict_types=1);

namespace App\Application\Command\CreateFoodChoice;

use App\Application\Command\CommandHandler;
use App\Domain\Model\FoodChoice\FoodChoice;
use App\Domain\Model\FoodChoice\FoodChoiceRepository;

final class CreateFoodChoiceCommandHandler implements CommandHandler
{
    private FoodChoiceRepository $repository;

    public function __construct(FoodChoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(CreateFoodChoiceCommand $command): void
    {
        $choice = FoodChoice::create(
            $command->getId(),
            $command->getGuestType(),
            $command->getCourse(),
            $command->getName()
        );

        $this->repository->store($choice);
    }
}
