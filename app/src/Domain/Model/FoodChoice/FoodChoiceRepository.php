<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice;

use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\EventStore;

final class FoodChoiceRepository
{
    public function __construct(private EventStore $eventStore)
    {
    }

    public function store(FoodChoice $choice): void
    {
        $this->eventStore->store($choice->flushEvents());
    }

    public function get(FoodChoiceId $id): FoodChoice
    {
        $events = $this->eventStore->get(AggregateName::fromString(FoodChoice::AGGREGATE_NAME), $id);

        if ($events->isEmpty()) {
            throw new FoodChoiceNotFound($id);
        }

        return FoodChoice::buildFrom($events);
    }
}
