<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
final class EventStreamPointer
{
    public function __construct(private string $position)
    {
    }

    public static function fromInt(int $position): self
    {
        return new self((string) $position);
    }

    public static function beginning(): self
    {
        return new self('00000000000000000000000000000000');
    }

    public function toInt(): int
    {
        return (int) $this->position;
    }

    public function toString(): string
    {
        return $this->position;
    }

    public function equals(self $that): bool
    {
        return $this->position === $that->position;
    }
}
