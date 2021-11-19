<?php declare(strict_types=1);

namespace App\Domain\Model\Shared;

/** @psalm-immutable */
enum GuestType
{
    case Adult;
    case Child;
    case Baby;

    public static function fromString(string $type): self
    {
        return match ($type) {
            'adult' => self::Adult,
            'child' => self::Child,
            'baby' => self::Baby,
            default => throw new \DomainException(\sprintf("Invalid guest type '%s' supplied", $type)),
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::Adult => 'adult',
            self::Child => 'child',
            self::Baby => 'baby',
        };
    }

    public function equals(self $that): bool
    {
        return $this === $that;
    }
}
