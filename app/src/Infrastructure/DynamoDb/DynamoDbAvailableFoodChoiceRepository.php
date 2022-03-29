<?php declare(strict_types=1);

namespace App\Infrastructure\DynamoDb;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceNotFound;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\GetItemInput;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbAvailableFoodChoiceRepository implements AvailableFoodChoiceRepository
{
    private const PK_NAMESPACE = 'available_food_choice';

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
                    'PK' => ['S' => \sprintf('%s#id#%s', self::PK_NAMESPACE, $choice->getId())],
                    'SK' => ['S' => '-'],
                    'Id' => ['S' => $choice->getId()],
                    'Course' => ['S' => $choice->getCourse()],
                    'GuestType' => ['S' => $choice->getGuestType()],
                    'Name' => ['S' => $choice->getName()],
                    'GSI1PK' => ['S' => \sprintf('%s#guest_type#%s', self::PK_NAMESPACE, $choice->getGuestType())],
                    'GSI1SK' => ['S' => \sprintf('course#%s#id#%s', $choice->getCourse(), $choice->getId())],
                    'GSI2PK' => ['S' => \sprintf('%s#all', self::PK_NAMESPACE)],
                    'GSI2SK' => ['S' => \sprintf('guest_type#%s#course#%s#id#%s', $choice->getGuestType(), $choice->getCourse(), $choice->getId())],
                ],
            ])
        );
    }

    public function get(string $id): AvailableFoodChoice
    {
        $result = $this->client->getItem(
            new GetItemInput([
                'TableName' => $this->tableName,
                'Key' => [
                    'PK' => ['S' => \sprintf('%s#id#%s', self::PK_NAMESPACE, $id)],
                    'SK' => ['S' => '-'],
                ],
            ])
        );

        if ($item = $result->getItem()) {
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
                            'GSI1PK' => ['S' => \sprintf('%s#guest_type#%s', self::PK_NAMESPACE, $guestType)],
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
                            'GSI2PK' => ['S' => \sprintf('%s#all', self::PK_NAMESPACE)],
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
