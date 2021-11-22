<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

interface AvailableFoodChoiceRepository
{
    public function store(AvailableFoodChoice $choice): void;

    /** @throws AvailableFoodChoiceNotFound */
    public function get(string $id): AvailableFoodChoice;

    /** @return array{starter: array, main: array, dessert: array} */
    public function getCoursesByGuestType(string $guestType): array;

    /** @return AvailableFoodChoice[] */
    public function all(): array;
}
