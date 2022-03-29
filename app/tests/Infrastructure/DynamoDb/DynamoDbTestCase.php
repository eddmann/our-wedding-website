<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\DynamoDb;

use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DynamoDbTestCase extends KernelTestCase
{
    protected DynamoDbClient $client;
    protected string $eventStoreTableName;
    protected string $projectionTableName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::getContainer()->get(DynamoDbClient::class);
        $this->eventStoreTableName = self::getContainer()->getParameter('dynamodb_event_store_table');
        $this->projectionTableName = self::getContainer()->getParameter('dynamodb_projection_table');
    }

    protected function tearDown(): void
    {
        $this->clearEventStoreTable();
        $this->clearProjectionTable();

        parent::tearDown();
    }

    private function clearEventStoreTable(): void
    {
        $result = $this->client->scan(['TableName' => $this->eventStoreTableName]);

        foreach ($result->getItems() as $item) {
            $this->client->deleteItem([
                'TableName' => $this->eventStoreTableName,
                'Key' => [
                    'AggregateId' => ['S' => $item['AggregateId']->getS()],
                    'AggregateVersion' => ['S' => $item['AggregateVersion']->getS()],
                ],
            ]);
        }
    }

    private function clearProjectionTable(): void
    {
        $result = $this->client->scan(['TableName' => $this->projectionTableName]);

        foreach ($result->getItems() as $item) {
            $this->client->deleteItem([
                'TableName' => $this->projectionTableName,
                'Key' => [
                    'PK' => ['S' => $item['PK']->getS()],
                    'SK' => ['S' => $item['SK']->getS()],
                ],
            ]);
        }
    }
}
