<?php declare(strict_types=1);

namespace App\Application\Command\AuthenticateInvite;

use App\Application\Command\Command;
use App\Domain\Model\Invite\InviteCode;

final class AuthenticateInviteCommand implements Command
{
    private InviteCode $code;

    public function __construct(string $code)
    {
        $this->code = InviteCode::fromString($code);
    }

    public function getCode(): InviteCode
    {
        return $this->code;
    }
}
