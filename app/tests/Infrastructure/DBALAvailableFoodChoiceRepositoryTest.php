<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Infrastructure\DBALAvailableFoodChoiceRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALAvailableFoodChoiceRepositoryTest extends KernelTestCase
{
    private AvailableFoodChoiceRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->repository = new DBALAvailableFoodChoiceRepository(
            $this->connection = self::getContainer()->get(Connection::class),
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

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
}
