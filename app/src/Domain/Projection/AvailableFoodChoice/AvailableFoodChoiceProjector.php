<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

use App\Domain\Helpers\Projector;
use App\Domain\Model\FoodChoice\Event\FoodChoiceWasCreated;

final class AvailableFoodChoiceProjector extends Projector
{
    public function __construct(private AvailableFoodChoiceRepository $repository)
    {
    }

    public function reset(): void
    {
    }

    public function getName(): string
    {
        return 'available_food_choice';
    }

    protected function handleFoodChoiceWasCreated(FoodChoiceWasCreated $event): void
    {
        $choice = new AvailableFoodChoice(
            $event->getAggregateId()->toString(),
            $event->getCourse()->toString(),
            $event->getGuestType()->toString(),
            $event->getName()->toString()
        );

        $this->repository->store($choice);
    }
}
