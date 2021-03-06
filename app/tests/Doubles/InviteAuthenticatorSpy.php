<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Model\Invite\InviteAuthenticator;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;

final class InviteAuthenticatorSpy implements InviteAuthenticator
{
    private ?InviteId $id = null;

    public function getLastLoginInviteId(): ?InviteId
    {
        return $this->id;
    }

    public function login(InviteId $id, InviteType $type): void
    {
        $this->id = $id;
    }
}
