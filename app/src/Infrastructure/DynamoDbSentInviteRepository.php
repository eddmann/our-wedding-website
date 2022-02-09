<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Projection\SentInvite\SentInvite;
use App\Domain\Projection\SentInvite\SentInviteNotFound;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbSentInviteRepository implements SentInviteRepository
{
    private const ALL_PK = 'sent_invite#all'; // currently a hot key

    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    public function store(SentInvite $invite): void
    {
        $this->client->putItem(
            new PutItemInput([
                'TableName' => $this->tableName,
                'Item' => [
                    'PK' => ['S' => \sprintf('sent_invite#id#%s', $invite->getId())],
                    'SK' => ['S' => '1'],
                    'Id' => ['S' => $invite->getId()],
                    'Code' => ['S' => $invite->getCode()],
                    'Type' => ['S' => $invite->getType()],
                    'InvitedGuests' => ['S' => \json_encode_array($invite->getInvitedGuests())],
                    'SubmittedAt' => ['S' => $invite->getSubmittedAt() ? \datetime_timestamp($invite->getSubmittedAt()) : ''],
                    'LastAuthenticatedAt' => ['S' => $invite->getLastAuthenticatedAt() ? \datetime_timestamp($invite->getLastAuthenticatedAt()) : ''],
                    'GSI1PK' => ['S' => \sprintf('sent_invite#code#%s', $invite->getCode())],
                    'GSI1SK' => ['S' => \sprintf('id#%s', $invite->getId())],
                    'GSI2PK' => ['S' => self::ALL_PK],
                    'GSI2SK' => ['S' => \sprintf('id#%s', $invite->getId())],
                ],
            ])
        );
    }

    public function get(string $id): SentInvite
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'KeyConditions' => [
                    'PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'PK' => ['S' => \sprintf('sent_invite#id#%s', $id)],
                        ],
                    ],
                ],
                'Limit' => 1,
            ])
        );

        foreach ($result->getItems() as $item) {
            return $this->toSentInvite($item);
        }

        throw new SentInviteNotFound($id);
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
            [$this, 'toSentInvite'],
            \iterator_to_array($result->getIterator())
        );
    }

    private function toSentInvite(array $item): SentInvite
    {
        return new SentInvite(
            $item['Id']->getS(),
            $item['Code']->getS(),
            $item['Type']->getS(),
            \json_decode_array($item['InvitedGuests']->getS()),
            $item['SubmittedAt']->getS() ? new \DateTimeImmutable($item['SubmittedAt']->getS()) : null,
            $item['LastAuthenticatedAt']->getS() ? new \DateTimeImmutable($item['LastAuthenticatedAt']->getS()) : null
        );
    }
}
