<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Invite\InviteAuthenticator;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;

final class SymfonyInviteAuthenticator implements InviteAuthenticator
{
    public function login(InviteId $id, InviteType $type): void
    {
    }
}
