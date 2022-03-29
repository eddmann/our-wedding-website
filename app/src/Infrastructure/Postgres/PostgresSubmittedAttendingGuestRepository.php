<?php declare(strict_types=1);

namespace App\Infrastructure\Postgres;

use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestNotFound;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use Doctrine\DBAL\Connection;

final class PostgresSubmittedAttendingGuestRepository implements SubmittedAttendingGuestRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function store(SubmittedAttendingGuest $guest): void
    {
        $sql = '
            INSERT INTO submitted_attending_guest_projection
            VALUES (:id, :inviteId, :inviteType, :guestType, :name, :chosenFoodChoices)
            ON CONFLICT (id) DO UPDATE SET
                invite_id = :inviteId, invite_type = :inviteType, guest_type = :guestType,
                name = :name, chosen_food_choices = :chosenFoodChoices
        ';

        $this->connection->executeStatement(
            $sql,
            [
                'id' => $guest->getId(),
                'inviteId' => $guest->getInviteId(),
                'inviteType' => $guest->getInviteType(),
                'guestType' => $guest->getGuestType(),
                'name' => $guest->getName(),
                'chosenFoodChoices' => \json_encode_array($guest->getChosenFoodChoices()),
            ]
        );
    }

    public function get(string $id): SubmittedAttendingGuest
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM submitted_attending_guest_projection WHERE id = :id',
            \compact('id')
        );

        if ($row = $result->fetchAssociative()) {
            return $this->toSubmittedAttendingGuest($row);
        }

        throw new SubmittedAttendingGuestNotFound($id);
    }

    public function getByInviteId(string $inviteId): array
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM submitted_attending_guest_projection WHERE invite_id = :inviteId',
            \compact('inviteId')
        );

        return \array_map([$this, 'toSubmittedAttendingGuest'], $result->fetchAllAssociative());
    }

    public function all(): array
    {
        $result = $this->connection->executeQuery('SELECT * FROM submitted_attending_guest_projection');

        return \array_map([$this, 'toSubmittedAttendingGuest'], $result->fetchAllAssociative());
    }

    private function toSubmittedAttendingGuest(array $row): SubmittedAttendingGuest
    {
        return new SubmittedAttendingGuest(
            $row['id'],
            $row['invite_id'],
            $row['invite_type'],
            $row['guest_type'],
            $row['name'],
            \json_decode_array($row['chosen_food_choices'])
        );
    }
}
