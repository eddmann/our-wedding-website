<?php declare(strict_types=1);

namespace App\Tests\Domain\Model\Invite;

use App\Domain\Model\Invite\InviteCode;
use PHPUnit\Framework\TestCase;

final class InviteCodeTest extends TestCase
{
    public function test_valid_code_is_generated(): void
    {
        self::assertMatchesRegularExpression('/^[A-Z][0-9][A-Z][0-9]$/', InviteCode::generate()->toString());
    }

    public function test_valid_code_is_created_from_string(): void
    {
        self::assertEquals('A1B2', InviteCode::fromString('A1B2')->toString());
    }

    public function test_unable_to_construct_invalid_code_from_string(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Invite code '1111' is not valid");

        InviteCode::fromString('1111');
    }
}
