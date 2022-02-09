<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Infrastructure\DynamoDbAvailableFoodChoiceRepository;
use AsyncAws\DynamoDb\DynamoDbClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DynamoDbAvailableFoodChoiceRepositoryTest extends KernelTestCase
{
    private AvailableFoodChoiceRepository $repository;
    private DynamoDbClient $client;
    private string $tableName;

    protected function setUp(): void
    {
        $this->repository = new DynamoDbAvailableFoodChoiceRepository(
            $this->client = self::getContainer()->get(DynamoDbClient::class),
            $this->tableName = $this->getContainer()->getParameter('projection_table_name'),
        );
    }

    protected function tearDown(): void
    {
        $this->clearProjectionTable();

        parent::tearDown();
    }

    public function test_it_persists_and_hydrates_available_food_choice(): void
    {
        $choice = new AvailableFoodChoice(
            $foodChoiceId = FoodChoiceId::generate()->toString(),
            'starter',
            'adult',
            'Starter'
        );

        $this->repository->store($choice);

        self::assertEquals(
            new AvailableFoodChoice(
                $foodChoiceId,
                'starter',
                'adult',
                'Starter'
            ),
            $this->repository->get($foodChoiceId)
        );
    }

    public function test_it_fetches_courses_by_guest_type(): void
    {
        $starter = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'starter', 'adult', 'Starter');
        $main = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'main', 'adult', 'Main');
        $dessert = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'dessert', 'adult', 'Dessert');

        $this->repository->store($starter);
        $this->repository->store($main);
        $this->repository->store($dessert);

        self::assertEquals(
            [
                'starter' => [$starter],
                'main' => [$main],
                'dessert' => [$dessert],
            ],
            $this->repository->getCoursesByGuestType('adult')
        );
    }

    public function test_it_fetches_all_food_choices(): void
    {
        $starter = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'starter', 'adult', 'Starter');
        $main = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'main', 'adult', 'Main');
        $dessert = new AvailableFoodChoice(FoodChoiceId::generate()->toString(), 'dessert', 'adult', 'Dessert');

        $this->repository->store($starter);
        $this->repository->store($main);
        $this->repository->store($dessert);

        self::assertEquals(
            [$dessert, $main, $starter],
            $this->repository->all()
        );
    }

    private function clearProjectionTable(): void
    {
        $result = $this->client->scan(['TableName' => $this->tableName]);

        foreach ($result->getItems() as $item) {
            $this->client->deleteItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'PK' => ['S' => $item['PK']->getS()],
                    'SK' => ['S' => $item['SK']->getS()],
                ],
            ]);
        }
    }
}
