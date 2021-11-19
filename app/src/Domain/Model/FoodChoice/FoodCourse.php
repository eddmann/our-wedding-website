<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice;

/** @psalm-immutable */
enum FoodCourse
{
    case Starter;
    case Main;
    case Dessert;

    public static function fromString(string $course): self
    {
        return match ($course) {
            'starter' => self::Starter,
            'main' => self::Main,
            'dessert' => self::Dessert,
            default => throw new \DomainException(\sprintf("Invalid food course '%s' supplied", $course)),
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::Starter => 'starter',
            self::Main => 'main',
            self::Dessert => 'dessert',
        };
    }

    public function equals(self $that): bool
    {
        return $this === $that;
    }
}
