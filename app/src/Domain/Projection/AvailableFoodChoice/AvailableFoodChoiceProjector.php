<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Model\FoodChoice\Event\FoodChoiceWasCreated;

final class AvailableFoodChoiceProjector extends AggregateEventsSubscriber
{
    public function __construct(private AvailableFoodChoiceRepository $repository)
    {
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
