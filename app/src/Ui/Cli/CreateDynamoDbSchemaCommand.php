<?php declare(strict_types=1);

namespace App\Ui\Cli;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\Enum\BillingMode;
use AsyncAws\DynamoDb\Enum\KeyType;
use AsyncAws\DynamoDb\Enum\ProjectionType;
use AsyncAws\DynamoDb\Input\CreateTableInput;
use AsyncAws\DynamoDb\ValueObject\AttributeDefinition;
use AsyncAws\DynamoDb\ValueObject\GlobalSecondaryIndex;
use AsyncAws\DynamoDb\ValueObject\KeySchemaElement;
use AsyncAws\DynamoDb\ValueObject\Projection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateDynamoDbSchemaCommand extends Command
{
    public function __construct(
        private DynamoDbClient $client,
        private string $eventTableName,
        private string $projectionTableName
    ) {
        parent::__construct('app:create-dynamodb-schema');

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Delete existing matching schema (if present)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isForce = $input->getOption('force');

        $output->writeln(\sprintf('Creating DynamoDB schema (force: %s)', $isForce ? 'true' : 'false'));

        if ($isForce) {
            $this->dropTable($this->eventTableName);
            $this->dropTable($this->projectionTableName);
        }

        $this->createEventTable($this->eventTableName);
        $output->writeln("Created {$this->eventTableName} table");

        $this->createProjectionTable($this->projectionTableName);
        $output->writeln("Created {$this->projectionTableName} table");

        return 0;
    }

    private function dropTable(string $tableName): void
    {
        try {
            $this->client->deleteTable(['TableName' => $tableName]);
        } catch (\Exception $exception) {
        }
    }

    private function createEventTable(string $tableName): void
    {
        $this->client->createTable(
            new CreateTableInput([
                'TableName' => $tableName,
                'AttributeDefinitions' => [
                    new AttributeDefinition(['AttributeName' => 'AggregateId', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'AggregateVersion', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'EventSequencePartition', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'EventSequenceOccurredAt', 'AttributeType' => 'S']),
                ],
                'KeySchema' => [
                    new KeySchemaElement(['AttributeName' => 'AggregateId', 'KeyType' => KeyType::HASH]),
                    new KeySchemaElement(['AttributeName' => 'AggregateVersion', 'KeyType' => KeyType::RANGE]),
                ],
                'GlobalSecondaryIndexes' => [
                    new GlobalSecondaryIndex([
                        'IndexName' => 'EventSequence',
                        'KeySchema' => [
                            new KeySchemaElement(['AttributeName' => 'EventSequencePartition', 'KeyType' => KeyType::HASH]),
                            new KeySchemaElement(['AttributeName' => 'EventSequenceOccurredAt', 'KeyType' => KeyType::RANGE]),
                        ],
                        'Projection' => new Projection(['ProjectionType' => ProjectionType::ALL]),
                    ]),
                ],
                'BillingMode' => BillingMode::PAY_PER_REQUEST,
            ])
        );

        $this->client->tableExists(['TableName' => $tableName])->wait();
    }

    private function createProjectionTable(string $tableName): void
    {
        $this->client->createTable(
            new CreateTableInput([
                'TableName' => $tableName,
                'AttributeDefinitions' => [
                    new AttributeDefinition(['AttributeName' => 'PK', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'SK', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'GSI1PK', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'GSI1SK', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'GSI2PK', 'AttributeType' => 'S']),
                    new AttributeDefinition(['AttributeName' => 'GSI2SK', 'AttributeType' => 'S']),
                ],
                'KeySchema' => [
                    new KeySchemaElement(['AttributeName' => 'PK', 'KeyType' => KeyType::HASH]),
                    new KeySchemaElement(['AttributeName' => 'SK', 'KeyType' => KeyType::RANGE]),
                ],
                'GlobalSecondaryIndexes' => [
                    new GlobalSecondaryIndex([
                        'IndexName' => 'GSI1',
                        'KeySchema' => [
                            new KeySchemaElement(['AttributeName' => 'GSI1PK', 'KeyType' => KeyType::HASH]),
                            new KeySchemaElement(['AttributeName' => 'GSI1SK', 'KeyType' => KeyType::RANGE]),
                        ],
                        'Projection' => new Projection(['ProjectionType' => ProjectionType::ALL]),
                    ]),
                    new GlobalSecondaryIndex([
                        'IndexName' => 'GSI2',
                        'KeySchema' => [
                            new KeySchemaElement(['AttributeName' => 'GSI2PK', 'KeyType' => KeyType::HASH]),
                            new KeySchemaElement(['AttributeName' => 'GSI2SK', 'KeyType' => KeyType::RANGE]),
                        ],
                        'Projection' => new Projection(['ProjectionType' => ProjectionType::ALL]),
                    ]),
                ],
                'BillingMode' => BillingMode::PAY_PER_REQUEST,
            ])
        );

        $this->client->tableExists(['TableName' => $tableName])->wait();
    }
}
