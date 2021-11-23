<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceNotFound;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use Doctrine\DBAL\Connection;

final class DBALAvailableFoodChoiceRepository implements AvailableFoodChoiceRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function store(AvailableFoodChoice $choice): void
    {
        $sql = '
            INSERT INTO available_food_choice_projection
            VALUES (:id, :name, :guestType, :course)
            ON CONFLICT (id) DO UPDATE SET name = :name, guest_type = :guestType, course = :course
        ';

        $this->connection->executeStatement(
            $sql,
            [
                'id' => $choice->getId(),
                'course' => $choice->getCourse(),
                'guestType' => $choice->getGuestType(),
                'name' => $choice->getName(),
            ]
        );
    }

    public function get(string $id): AvailableFoodChoice
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM available_food_choice_projection WHERE id = :id',
            \compact('id')
        );

        if ($row = $result->fetchAssociative()) {
            return $this->toAvailableFoodChoice($row);
        }

        throw new AvailableFoodChoiceNotFound($id);
    }

    /** @return array{starter: array, main: array, dessert: array} */
    public function getCoursesByGuestType(string $guestType): array
    {
        $result = $this->connection->executeQuery(
            'SELECT * FROM available_food_choice_projection WHERE guest_type = :guestType',
            \compact('guestType')
        );

        return \array_reduce(
            $result->fetchAllAssociative(),
            fn (array $acc, array $row): array => [
                ...$acc,
                $row['course'] => [...$acc[$row['course']], $this->toAvailableFoodChoice($row)],
            ],
            ['starter' => [], 'main' => [], 'desert' => []]
        );
    }

    public function all(): array
    {
        $result = $this->connection->executeQuery('SELECT * FROM available_food_choice_projection');

        return \array_map([$this, 'toAvailableFoodChoice'], $result->fetchAllAssociative());
    }

    private function toAvailableFoodChoice(array $row): AvailableFoodChoice
    {
        return new AvailableFoodChoice(
            $row['id'],
            $row['course'],
            $row['guest_type'],
            $row['name']
        );
    }
}
