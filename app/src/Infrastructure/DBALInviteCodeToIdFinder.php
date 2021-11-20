<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\AuthenticateInvite\InviteCodeToIdFinder;
use App\Domain\Model\Invite\{InviteCode, InviteId};
use Doctrine\DBAL\Connection;

final class DBALInviteCodeToIdFinder implements InviteCodeToIdFinder
{
    public function __construct(private Connection $connection)
    {
    }

    public function find(InviteCode $code): InviteId
    {
        return InviteId::generate();
    }
}
