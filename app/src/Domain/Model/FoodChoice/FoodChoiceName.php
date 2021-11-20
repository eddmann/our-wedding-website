<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice;

/** @psalm-immutable */
final class FoodChoiceName
{
    public function __construct(private string $name)
    {
        if ($name === '') {
            throw new \DomainException('Food choices must have a name');
        }
    }

    public function toString(): string
    {
        return $this->name;
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function equals(self $that): bool
    {
        return $this->name === $that->name;
    }
}
