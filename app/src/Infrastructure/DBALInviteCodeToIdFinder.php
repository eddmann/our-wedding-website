<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\AuthenticateInvite\{InviteCodeNotFound, InviteCodeToIdFinder};
use App\Domain\Model\Invite\{InviteCode, InviteId};
use Doctrine\DBAL\Connection;

final class DBALInviteCodeToIdFinder implements InviteCodeToIdFinder
{
    public function __construct(private Connection $connection)
    {
    }

    public function find(InviteCode $code): InviteId
    {
        $result = $this->connection->executeQuery(
            'SELECT id FROM sent_invite_projection WHERE code = :code',
            ['code' => $code->toString()]
        );

        if ($id = $result->fetchOne()) {
            return InviteId::fromString($id);
        }

        throw new InviteCodeNotFound($code);
    }
}
