<?php declare(strict_types=1);

namespace App\Infrastructure\DynamoDb;

use App\Application\Command\AuthenticateInvite\InviteCodeNotFound;
use App\Application\Command\AuthenticateInvite\InviteCodeToIdFinder;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbInviteCodeToIdFinder implements InviteCodeToIdFinder
{
    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    public function find(InviteCode $code): InviteId
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI1',
                'KeyConditions' => [
                    'GSI1PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1PK' => ['S' => \sprintf('sent_invite#code#%s', $code->toString())],
                        ],
                    ],
                ],
                'Limit' => 1,
            ])
        );

        foreach ($result->getItems(true) as $item) {
            if ($id = $item['Id']->getS()) {
                return InviteId::fromString($id);
            }
        }

        throw new InviteCodeNotFound($code);
    }
}
