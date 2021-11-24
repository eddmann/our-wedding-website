<?php declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;

final class FoodChoiceListingQuery
{
    public function __construct(private AvailableFoodChoiceRepository $foodChoiceRepository)
    {
    }

    public function query(): array
    {
        return \array_map(
            static fn (AvailableFoodChoice $choice) => [
                'id' => $choice->getId(),
                'name' => $choice->getName(),
                'guestType' => $choice->getGuestType(),
                'course' => $choice->getCourse(),
            ],
            $this->foodChoiceRepository->all()
        );
    }
}
