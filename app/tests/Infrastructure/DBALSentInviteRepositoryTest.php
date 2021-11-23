<?php declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Domain\Model\Invite\{InviteCode, InviteId};
use App\Domain\Projection\SentInvite\{SentInvite, SentInviteRepository};
use App\Infrastructure\DBALSentInviteRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALSentInviteRepositoryTest extends KernelTestCase
{
    private SentInviteRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->repository = new DBALSentInviteRepository(
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
            $inviteCode = InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        self::assertEquals(
            new SentInvite(
                $inviteId,
                $inviteCode,
                'day',
                []
            ),
            $this->repository->get($inviteId)
        );
    }

    public function test_it_updates_an_existing_sent_invite(): void
    {
        $invite = new SentInvite(
            $inviteId = InviteId::generate()->toString(),
            $inviteCode = InviteCode::generate()->toString(),
            'day',
            []
        );

        $this->repository->store($invite);

        $invite->submitted($submittedAt = new \DateTimeImmutable());

        $this->repository->store($invite);

        self::assertEquals(
            new SentInvite(
                $inviteId,
                $inviteCode,
                'day',
                [],
                $submittedAt
            ),
            $this->repository->get($inviteId)
        );
    }
}