<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\FoodChoice\FoodCourse;
use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Model\Shared\GuestType;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\Select;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DyanmoDbChosenFoodChoiceValidator implements ChosenFoodChoiceValidator
{
    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    /**
     * @psalm-mutation-free
     * @psalm-suppress ImpureMethodCall
     */
    public function isValid(GuestType $type, ChosenFoodChoices $choices): bool
    {
        return $this->isValidChoice($type, FoodCourse::Starter, $choices->getStarterId())
            && $this->isValidChoice($type, FoodCourse::Main, $choices->getMainId())
            && $this->isValidChoice($type, FoodCourse::Dessert, $choices->getDessertId());
    }

    private function isValidChoice(
        GuestType $type,
        FoodCourse $course,
        ?FoodChoiceId $id
    ): bool {
        if ($id === null) {
            return false;
        }

        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI1',
                'Select' => Select::COUNT,
                'KeyConditions' => [
                    'GSI1PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1PK' => ['S' => \sprintf('available_food_choice#guest_type#%s', $type->toString())],
                        ],
                    ],
                    'GSI1SK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1SK' => ['S' => \sprintf('course#%s#id#%s', $course->toString(), $id->toString())],
                        ],
                    ],
                ],
            ])
        );

        return $result->getCount() === 1;
    }
}
