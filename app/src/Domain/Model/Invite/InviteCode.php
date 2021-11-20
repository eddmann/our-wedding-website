<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

/** @psalm-immutable */
final class InviteCode
{
    private const ALPHA = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const NUMERIC = '123456789';

    private function __construct(private string $code)
    {
        if (1 !== \preg_match('/^[A-Z][1-9][A-Z][1-9]$/', $code)) {
            throw new \DomainException(\sprintf("Invite code '%s' is not valid", $code));
        }
    }

    public static function generate(): self
    {
        return new self(
            \sprintf(
                '%s%s%s%s',
                self::random(self::ALPHA),
                self::random(self::NUMERIC),
                self::random(self::ALPHA),
                self::random(self::NUMERIC)
            )
        );
    }

    public static function fromString(string $code): self
    {
        return new self(\mb_strtoupper($code));
    }

    public function toString(): string
    {
        return $this->code;
    }

    public function equals(self $that): bool
    {
        return $this->code === $that->code;
    }

    private static function random(string $permitted): string
    {
        return $permitted[\random_int(0, \mb_strlen($permitted) - 1)];
    }
}
