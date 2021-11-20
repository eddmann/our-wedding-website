<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Guest;

/** @psalm-immutable */
final class GuestName
{
    private function __construct(private string $name)
    {
        if ($name === '') {
            throw new \DomainException('Guests must have a name');
        }
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function equals(self $that): bool
    {
        return $this->name === $that->name;
    }
}
