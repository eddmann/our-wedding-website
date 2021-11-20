<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

interface InviteAuthenticator
{
    public function login(InviteId $id, InviteType $type): void;
}
