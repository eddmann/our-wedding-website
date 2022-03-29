<?php declare(strict_types=1);

namespace App\Infrastructure\Postgres;

use App\Application\Command\AuthenticateInvite\InviteCodeNotFound;
use App\Application\Command\AuthenticateInvite\InviteCodeToIdFinder;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use Doctrine\DBAL\Connection;

final class PostgresInviteCodeToIdFinder implements InviteCodeToIdFinder
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
