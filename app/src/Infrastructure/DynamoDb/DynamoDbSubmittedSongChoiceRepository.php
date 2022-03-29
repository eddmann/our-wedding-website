<?php declare(strict_types=1);

namespace App\Infrastructure\DynamoDb;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;

final class DynamoDbSubmittedSongChoiceRepository implements SubmittedSongChoiceRepository
{
    private const PK_NAMESPACE = 'submitted_song_choice';

    public function __construct(
        private DynamoDbClient $client,
        private string $tableName
    ) {
    }

    public function store(SubmittedSongChoice $choice): void
    {
        $this->client->putItem(
            new PutItemInput([
                'TableName' => $this->tableName,
                'Item' => [
                    'PK' => ['S' => \sprintf('%s#all', self::PK_NAMESPACE)],
                    'SK' => ['S' => $choice->getSubmittedAt()->format('Uu')],
                    'Artist' => ['S' => $choice->getArtist()],
                    'Track' => ['S' => $choice->getTrack()],
                    'SubmittedAt' => ['S' => \datetime_timestamp($choice->getSubmittedAt())],
                ],
            ])
        );
    }

    public function all(): array
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'KeyConditions' => [
                    'PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'PK' => ['S' => \sprintf('%s#all', self::PK_NAMESPACE)],
                        ],
                    ],
                ],
            ])
        );

        return \array_map(
            /** @psalm-suppress PossiblyNullArgument */
            static fn (array $item): SubmittedSongChoice => new SubmittedSongChoice($item['Artist']->getS(), $item['Track']->getS(), new \DateTimeImmutable($item['SubmittedAt']->getS())),
            \iterator_to_array($result->getIterator())
        );
    }
}
