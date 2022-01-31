<?php declare(strict_types=1);

namespace App\Domain\Helpers;

use Symfony\Component\Uid\Uuid;

/** @psalm-immutable */
abstract class AggregateId
{
    private const NIL = '00000000-0000-0000-0000-000000000000';

    private function __construct(private string $id)
    {
        if (! Uuid::isValid($id)) {
            throw new \DomainException(\sprintf("%s '%s' is not valid", static::class, $id));
        }
    }

    public function toString(): string
    {
        return $this->id;
    }

    /** @psalm-suppress UnsafeInstantiation */
    public static function generate(): static
    {
        return new static(Uuid::v4()->toRfc4122());
    }

    /** @psalm-suppress UnsafeInstantiation */
    public static function fromString(string $id): static
    {
        return new static($id);
    }

    /** @psalm-suppress UnsafeInstantiation */
    public static function nil(): static
    {
        return new static(self::NIL);
    }

    public function equals(self $that): bool
    {
        if (static::class !== \get_class($that)) {
            throw new \DomainException('Unable to compare different ids');
        }

        return $this->id === $that->id;
    }

    public function isNil(): bool
    {
        return $this->id === self::NIL;
    }
}
