<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

/** @psalm-immutable */
enum InviteType
{
    case Day;
    case Evening;

    public static function fromString(string $type): self
    {
        return match ($type) {
            'day' => self::Day,
            'evening' => self::Evening,
            default => throw new \DomainException(\sprintf("Invalid invite type '%s' supplied", $type)),
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::Day => 'day',
            self::Evening => 'evening',
        };
    }

    public function equals(self $that): bool
    {
        return $this === $that;
    }
}
