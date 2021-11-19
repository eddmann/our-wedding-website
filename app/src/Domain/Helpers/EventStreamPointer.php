<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
final class EventStreamPointer
{
    private int $position;

    private function __construct(int $position)
    {
        $this->position = $position;
    }

    public static function fromInt(int $position): self
    {
        return new self($position);
    }

    public static function beginning(): self
    {
        return new self(0);
    }

    public function toInt(): int
    {
        return $this->position;
    }

    public function equals(self $that): bool
    {
        return $this->position === $that->position;
    }
}