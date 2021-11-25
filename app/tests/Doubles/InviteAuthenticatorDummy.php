<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Model\Invite\InviteAuthenticator;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;

final class InviteAuthenticatorDummy implements InviteAuthenticator
{
    public function login(InviteId $id, InviteType $type): void
    {
    }
}
