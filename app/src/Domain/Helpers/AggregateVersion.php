<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
final class AggregateVersion
{
    private function __construct(private int $version)
    {
    }

    public static function fromInt(int $version): self
    {
        return new self($version);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function next(): self
    {
        return new self($this->version + 1);
    }

    public function toInt(): int
    {
        return $this->version;
    }

    public function equals(self $that): bool
    {
        return $this->version === $that->version;
    }
}
