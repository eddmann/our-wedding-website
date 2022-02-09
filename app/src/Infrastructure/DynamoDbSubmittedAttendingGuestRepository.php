<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestNotFound;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbSubmittedAttendingGuestRepository implements SubmittedAttendingGuestRepository
{
    private const ALL_PK = 'submitted_attending_guest#all'; // currently a hot key

    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    public function store(SubmittedAttendingGuest $guest): void
    {
        $this->client->putItem(
            new PutItemInput([
                'TableName' => $this->tableName,
                'Item' => [
                    'PK' => ['S' => \sprintf('submitted_song_choice#id#%s', $guest->getId())],
                    'SK' => ['S' => '1'],
                    'Id' => ['S' => $guest->getId()],
                    'InviteId' => ['S' => $guest->getInviteId()],
                    'InviteType' => ['S' => $guest->getInviteType()],
                    'GuestType' => ['S' => $guest->getGuestType()],
                    'Name' => ['S' => $guest->getName()],
                    'ChosenFoodChoices' => ['S' => \json_encode_array($guest->getChosenFoodChoices())],
                    'GSI1PK' => ['S' => \sprintf('submitted_attending_guest#invite_id#%s', $guest->getInviteId())],
                    'GSI1SK' => ['S' => \sprintf('id#%s', $guest->getId())],
                    'GSI2PK' => ['S' => self::ALL_PK],
                    'GSI2SK' => ['S' => \sprintf('id#%s', $guest->getId())],
                ],
            ])
        );
    }

    public function get(string $id): SubmittedAttendingGuest
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'KeyConditions' => [
                    'PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'PK' => ['S' => \sprintf('submitted_song_choice#id#%s', $id)],
                        ],
                    ],
                ],
                'Limit' => 1,
            ])
        );

        foreach ($result->getItems() as $item) {
            return $this->toSubmittedAttendingGuest($item);
        }

        throw new SubmittedAttendingGuestNotFound($id);
    }

    public function getByInviteId(string $inviteId): array
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI1',
                'KeyConditions' => [
                    'GSI1PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1PK' => ['S' => \sprintf('submitted_attending_guest#invite_id#%s', $inviteId)],
                        ],
                    ],
                ],
            ])
        );

        return \array_map(
            [$this, 'toSubmittedAttendingGuest'],
            \iterator_to_array($result->getIterator())
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
            [$this, 'toSubmittedAttendingGuest'],
            \iterator_to_array($result->getIterator())
        );
    }

    private function toSubmittedAttendingGuest(array $item): SubmittedAttendingGuest
    {
        return new SubmittedAttendingGuest(
            $item['Id']->getS(),
            $item['InviteId']->getS(),
            $item['InviteType']->getS(),
            $item['GuestType']->getS(),
            $item['Name']->getS(),
            \json_decode_array($item['ChosenFoodChoices']->getS())
        );
    }
}
