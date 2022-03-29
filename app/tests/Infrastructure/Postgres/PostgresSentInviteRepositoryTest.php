<?php declare(strict_types=1);

namespace App\Tests\Infrastructure\Postgres;

use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Projection\SentInvite\SentInvite;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use App\Infrastructure\Postgres\PostgresSentInviteRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PostgresSentInviteRepositoryTest extends KernelTestCase
{
    private SentInviteRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->repository = new PostgresSentInviteRepository(
            $this->connection = self::getContainer()->get(Connection::class),
        );

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();

        parent::tearDown();
    }

    public function test_it_persists_and_hydrates_sent_invite(): void
    {
        $invite = new SentInvite(
            $inviteId = InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        self::assertEquals(
            $invite,
            $this->repository->get($inviteId)
        );
    }

    public function test_it_updates_an_existing_sent_invite(): void
    {
        $invite = new SentInvite(
            $inviteId = InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        $invite->submitted(new \DateTimeImmutable());

        $this->repository->store($invite);

        self::assertEquals(
            $invite,
            $this->repository->get($inviteId)
        );
    }

    public function test_it_fetches_all_sent_invites(): void
    {
        $invite = new SentInvite(
            InviteId::generate()->toString(),
            InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        self::assertEquals(
            [$invite],
            $this->repository->all()
        );
    }
}
