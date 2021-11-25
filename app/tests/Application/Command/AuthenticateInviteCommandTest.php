<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommand;
use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommandHandler;
use App\Application\Command\AuthenticateInvite\InviteCodeNotFound;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use App\Application\Command\CreateInvite\CreateInviteCommandHandler;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteRepository;
use App\Tests\Doubles\DomainEventBusDummy;
use App\Tests\Doubles\InviteAuthenticatorSpy;

final class AuthenticateInviteCommandTest extends CommandTestCase
{
    private InviteRepository $repository;
    private InviteAuthenticatorSpy $authenticator;
    private AuthenticateInviteCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new AuthenticateInviteCommandHandler(
            $this->repository = new InviteRepository($this->eventStore),
            $this->eventStore,
            $this->authenticator = new InviteAuthenticatorSpy()
        );
    }

    public function test_it_authenticates_an_invite_login(): void
    {
        $invite = $this->createInvite(
            new CreateInviteCommand(
                'day',
                [
                    ['type' => 'adult', 'name' => 'Adult'],
                    ['type' => 'child', 'name' => 'Child'],
                    ['type' => 'baby', 'name' => 'Baby'],
                ]
            )
        );

        $command = new AuthenticateInviteCommand($invite->getInviteCode()->toString());

        ($this->handler)($command);

        $invite = $this->repository->get($invite->getAggregateId());

        self::assertNotNull($invite->getLastAuthenticatedAt());
        self::assertTrue($invite->getAggregateId()->equals($this->authenticator->getLastLoginInviteId()));
    }

    public function test_it_is_unable_to_authenticate_invite_with_invalid_code(): void
    {
        $this->expectException(InviteCodeNotFound::class);

        $command = new AuthenticateInviteCommand(InviteCode::generate()->toString());

        ($this->handler)($command);
    }

    private function createInvite(CreateInviteCommand $command): Invite
    {
        $handler = new CreateInviteCommandHandler($this->repository, new DomainEventBusDummy());

        $handler($command);

        return $this->repository->get($command->getId());
    }
}
