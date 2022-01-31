<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

final class AvailableFoodChoice
{
    public function __construct(
        private string $id,
        private string $course,
        private string $guestType,
        private string $name
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCourse(): string
    {
        return $this->course;
    }

    public function getGuestType(): string
    {
        return $this->guestType;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
