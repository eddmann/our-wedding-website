<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Input\PutItemInput;
use AsyncAws\DynamoDb\Input\QueryInput;
use Symfony\Component\Uid\Uuid;

final class DynamoDbSubmittedSongChoiceRepository implements SubmittedSongChoiceRepository
{
    private const ALL_PK = 'submitted_song_choice#all'; // currently a hot key

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
                    'PK' => ['S' => \sprintf('submitted_song_choice#%s', (string) Uuid::v4())],
                    'SK' => ['S' => \sprintf('artist#%s#track#%s', $choice->getArtist(), $choice->getTrack())],
                    'Artist' => ['S' => $choice->getArtist()],
                    'Track' => ['S' => $choice->getTrack()],
                    'SubmittedAt' => ['S' => \datetime_timestamp($choice->getSubmittedAt())],
                    'GSI1PK' => ['S' => self::ALL_PK],
                    'GSI1SK' => ['S' => $choice->getSubmittedAt()->format('Uu')],
                ],
            ])
        );
    }

    public function all(): array
    {
        $result = $this->client->query(
            new QueryInput([
                'TableName' => $this->tableName,
                'IndexName' => 'GSI1',
                'KeyConditions' => [
                    'GSI1PK' => [
                        'ComparisonOperator' => 'EQ',
                        'AttributeValueList' => [
                            'GSI1PK' => ['S' => self::ALL_PK],
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
