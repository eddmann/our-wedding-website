<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceNotFound;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbAvailableFoodChoiceRepository implements AvailableFoodChoiceRepository
{
    private const ALL_PK = 'available_food_choice#all'; // currently a hot key

    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    public function store(AvailableFoodChoice $choice): void
    {
        $this->client->putItem(
            new PutItemInput([
                'TableName' => $this->tableName,
                'Item' => [
                    'PK' => ['S' => \sprintf('available_food_choice#id#%s', $choice->getId())],
                    'SK' => ['S' => '1'],
                    'Id' => ['S' => $choice->getId()],
                    'Course' => ['S' => $choice->getCourse()],
                    'GuestType' => ['S' => $choice->getGuestType()],
                    'Name' => ['S' => $choice->getName()],
                    'GSI1PK' => ['S' => \sprintf('available_food_choice#guest_type#%s', $choice->getGuestType())],
                    'GSI1SK' => ['S' => \sprintf('course#%s#id#%s', $choice->getCourse(), $choice->getId())],
                    'GSI2PK' => ['S' => self::ALL_PK],
                    'GSI2SK' => [
                        'S' => \sprintf(
                            '#guest_type#%s#course#%s#id#%s',
                            $choice->getGuestType(),
                            $choice->getCourse(),
                            $choice->getId()
                        ),
                    ],
                ],
            ])
        );
    }

    public function get(string $id): AvailableFoodChoice
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'KeyConditions' => [
                    'PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'PK' => ['S' => \sprintf('available_food_choice#id#%s', $id)],
                        ],
                    ],
                ],
                'Limit' => 1,
            ])
        );

        foreach ($result->getItems() as $item) {
            return $this->toAvailableFoodChoice($item);
        }

        throw new AvailableFoodChoiceNotFound($id);
    }

    /** @return array{starter: array, main: array, dessert: array} */
    public function getCoursesByGuestType(string $guestType): array
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI1',
                'KeyConditions' => [
                    'GSI1PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1PK' => ['S' => \sprintf('available_food_choice#guest_type#%s', $guestType)],
                        ],
                    ],
                ],
            ])
        );

        return \array_reduce(
            \iterator_to_array($result->getIterator()),
            fn (array $acc, array $item): array => [
                ...$acc,
                $item['Course']->getS() => [...$acc[$item['Course']->getS()], $this->toAvailableFoodChoice($item)],
            ],
            ['starter' => [], 'main' => [], 'dessert' => []]
        );
    }

    public function all(): array
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI2',
                'KeyConditions' => [
                    'GSI2PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI2PK' => ['S' => self::ALL_PK],
                        ],
                    ],
                ],
            ])
        );

        return \array_map(
            [$this, 'toAvailableFoodChoice'],
            \iterator_to_array($result->getIterator())
        );
    }

    private function toAvailableFoodChoice(array $item): AvailableFoodChoice
    {
        return new AvailableFoodChoice(
            $item['Id']->getS(),
            $item['Course']->getS(),
            $item['GuestType']->getS(),
            $item['Name']->getS()
        );
    }
}
