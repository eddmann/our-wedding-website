<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
final class AggregateName
{
    private function __construct(private string $name)
    {
        if ($name === '') {
            throw new \DomainException('Aggregates must have a name');
        }
    }

    public function toString(): string
    {
        return $this->name;
    }

    /** @psalm-mutation-free */
    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function equals(self $that): bool
    {
        return $this->name === $that->name;
    }
}
