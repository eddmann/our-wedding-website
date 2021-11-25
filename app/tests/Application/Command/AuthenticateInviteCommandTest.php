<?php declare(strict_types=1);

namespace App\Tests\Application\Command;

use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommand;
use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommandHandler;
use App\Application\Command\AuthenticateInvite\InviteCodeNotFound;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteRepository;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;
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
        $invite = Invite::create(
            $id = InviteId::generate(),
            $code = InviteCode::generate(),
            $inviteType = InviteType::Evening,
            [
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Child, GuestName::fromString('Child Name')),
                InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Baby, GuestName::fromString('Baby Name')),
            ]
        );

        $this->repository->store($invite);

        $command = new AuthenticateInviteCommand($code->toString());

        ($this->handler)($command);

        $invite = $this->repository->get($id);

        self::assertNotNull($invite->getLastAuthenticatedAt());
        self::assertTrue($id->equals($this->authenticator->getLastLoginInviteId()));
    }

    public function test_it_is_unable_to_authenticate_invite_with_invalid_code(): void
    {
        $this->expectException(InviteCodeNotFound::class);

        $command = new AuthenticateInviteCommand(InviteCode::generate()->toString());

        ($this->handler)($command);
    }
}
