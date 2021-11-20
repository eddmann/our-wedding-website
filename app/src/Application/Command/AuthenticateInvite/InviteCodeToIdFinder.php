<?php declare(strict_types=1);

namespace App\Application\Command\AuthenticateInvite;

use App\Domain\Model\Invite\{InviteCode, InviteId};

interface InviteCodeToIdFinder
{
    /** @throws InviteCodeNotFound */
    public function find(InviteCode $code): InviteId;
}
