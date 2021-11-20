<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Invite\{InviteAuthenticator, InviteId, InviteType};

final class SymfonyInviteAuthenticator implements InviteAuthenticator
{
    public function login(InviteId $id, InviteType $type): void
    {
    }
}
