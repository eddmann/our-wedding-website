<?php declare(strict_types=1);

namespace App\Application\Command\AuthenticateInvite;

use App\Application\Command\CommandHandler;
use App\Domain\Model\Invite\{InviteAuthenticator, InviteRepository};

final class AuthenticateInviteCommandHandler implements CommandHandler
{
    public function __construct(
        private InviteRepository $repository,
        private InviteCodeToIdFinder $codeToId,
        private InviteAuthenticator $authenticator
    ) {
    }

    public function __invoke(AuthenticateInviteCommand $command): void
    {
        $invite = $this->repository->get($this->codeToId->find($command->getCode()));

        $invite->authenticate($this->authenticator);

        $this->repository->store($invite);
    }
}
