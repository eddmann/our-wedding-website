<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Model\FoodChoice\Events\FoodChoiceWasCreated;

final class AvailableFoodChoiceProjector extends AggregateEventsSubscriber
{
    private AvailableFoodChoiceRepository $repository;

    public function __construct(AvailableFoodChoiceRepository $repository)
    {
        $this->repository = $repository;
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