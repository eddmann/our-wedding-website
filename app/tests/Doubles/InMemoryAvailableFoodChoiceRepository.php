<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceNotFound;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;

final class InMemoryAvailableFoodChoiceRepository implements AvailableFoodChoiceRepository
{
    /** @var AvailableFoodChoice[] */
    private $choices = [];

    public function store(AvailableFoodChoice $choice): void
    {
        $this->choices[$choice->getId()] = $choice;
    }

    public function get(string $id): AvailableFoodChoice
    {
        return $this->choices[$id] ?? throw new AvailableFoodChoiceNotFound($id);
    }

    /** @return array{starter: array, main: array, dessert: array} */
    public function getCoursesByGuestType(string $guestType): array
    {
        $choices = ['starter' => [], 'main' => [], 'dessert' => []];

        foreach ($this->choices as $choice) {
            if ($choice->getGuestType() === $guestType) {
                $choices[$choice->getCourse()][] = $choice;
            }
        }

        return $choices;
    }

    public function all(): array
    {
        return \array_values($this->choices);
    }
}
