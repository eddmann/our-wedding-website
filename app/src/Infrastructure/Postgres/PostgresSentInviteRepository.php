<?php declare(strict_types=1);

namespace App\Infrastructure\Postgres;

use App\Domain\Projection\SentInvite\SentInvite;
use App\Domain\Projection\SentInvite\SentInviteNotFound;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use Doctrine\DBAL\Connection;

final class PostgresSentInviteRepository implements SentInviteRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function store(SentInvite $invite): void
    {
        $sql = '
            INSERT INTO sent_invite_projection
            VALUES (:id, :code, :type, :invitedGuests, :submittedAt, :lastAuthenticatedAt)
            ON CONFLICT (id) DO UPDATE SET
                code = :code, type = :type, invited_guests = :invitedGuests,
                submitted_at = :submittedAt, last_authenticated_at = :lastAuthenticatedAt
        ';

        $this->connection->executeStatement(
            $sql,
            [
                'id' => $invite->getId(),
                'code' => $invite->getCode(),
                'type' => $invite->getType(),
                'invitedGuests' => \json_encode_array($invite->getInvitedGuests()),
                'submittedAt' => $invite->getSubmittedAt()?->format('Y-m-d H:i:s.u O'),
                'lastAuthenticatedAt' => $invite->getLastAuthenticatedAt()?->format('Y-m-d H:i:s.u O'),
            ]
        );
    }

    public function get(string $id): SentInvite
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM sent_invite_projection WHERE id = :id',
            \compact('id')
        );

        if ($row = $result->fetchAssociative()) {
            return $this->toSentInvite($row);
        }

        throw new SentInviteNotFound($id);
    }

    public function all(): array
    {
        $result = $this->connection->executeQuery('SELECT * FROM sent_invite_projection');

        return \array_map([$this, 'toSentInvite'], $result->fetchAllAssociative());
    }

    private function toSentInvite(array $row): SentInvite
    {
        return new SentInvite(
            $row['id'],
            $row['code'],
            $row['type'],
            \json_decode_array($row['invited_guests']),
            $row['submitted_at'] ? new \DateTimeImmutable($row['submitted_at']) : null,
            $row['last_authenticated_at'] ? new \DateTimeImmutable($row['last_authenticated_at']) : null
        );
    }
}
