<?php declare(strict_types=1);

namespace App\Infrastructure\Postgres;

use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Model\Shared\GuestType;
use Doctrine\DBAL\Connection;

final class PostgresChosenFoodChoiceValidator implements ChosenFoodChoiceValidator
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @psalm-mutation-free
     * @psalm-suppress ImpureMethodCall
     */
    public function isValid(GuestType $type, ChosenFoodChoices $choices): bool
    {
        $sql = "
            SELECT COUNT(*) = 3
            FROM available_food_choice_projection
            WHERE guest_type = :type
            AND (
                course = 'starter' AND id = :starterId OR
                course = 'main' AND id = :mainId OR
                course = 'dessert' AND id = :dessertId
            )
        ";

        $result = $this->connection->executeQuery(
            $sql,
            [
                'type' => $type->toString(),
                'starterId' => $choices->getStarterId()?->toString(),
                'mainId' => $choices->getMainId()?->toString(),
                'dessertId' => $choices->getDessertId()?->toString(),
            ]
        );

        return (bool) $result->fetchOne();
    }
}
