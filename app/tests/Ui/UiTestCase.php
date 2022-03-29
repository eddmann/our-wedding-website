<?php declare(strict_types=1);

namespace App\Tests\Ui;

use AsyncAws\DynamoDb\DynamoDbClient;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This ensures that the test app is configured with the desired ES/Projection backends.
 * Based on any combination, it ensures that all testing arrangements are removed after use.
 */
abstract class UiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function tearDown(): void
    {
        self::getContainer()->get(Connection::class)->rollBack();

        $this->clearDynamoDbEventTable();
        $this->clearDynamoDbProjectionTable();

        $this->clearTestCache();

        parent::tearDown();
    }

    protected function givenAppWithBackends(string $eventStoreBackend, string $projectionBackend): void
    {
        \putenv("EVENT_STORE_BACKEND={$eventStoreBackend}");
        \putenv("PROJECTION_BACKEND={$projectionBackend}");
        $this->clearTestCache();

        $this->client = self::createClient();
        $this->client->disableReboot();

        self::getContainer()->get(Connection::class)->beginTransaction();
    }

    private function clearTestCache(): void
    {
        (new Filesystem())->remove('/tmp/cache/test');
    }

    private function clearDynamoDbEventTable(): void
    {
        $dynamoDbClient = self::getContainer()->get(DynamoDbClient::class);
        $eventStoreTableName = self::getContainer()->getParameter('dynamodb_event_store_table');

        $result = $dynamoDbClient->scan(['TableName' => $eventStoreTableName]);

        foreach ($result->getItems() as $item) {
            $dynamoDbClient->deleteItem([
                'TableName' => $eventStoreTableName,
                'Key' => [
                    'AggregateId' => ['S' => $item['AggregateId']->getS()],
                    'AggregateVersion' => ['S' => $item['AggregateVersion']->getS()],
                ],
            ]);
        }
    }

    private function clearDynamoDbProjectionTable(): void
    {
        $dynamoDbClient = self::getContainer()->get(DynamoDbClient::class);
        $projectionTableName = self::getContainer()->getParameter('dynamodb_projection_table');

        $result = $dynamoDbClient->scan(['TableName' => $projectionTableName]);

        foreach ($result->getItems() as $item) {
            $dynamoDbClient->deleteItem([
                'TableName' => $projectionTableName,
                'Key' => [
                    'PK' => ['S' => $item['PK']->getS()],
                    'SK' => ['S' => $item['SK']->getS()],
                ],
            ]);
        }
    }
}
