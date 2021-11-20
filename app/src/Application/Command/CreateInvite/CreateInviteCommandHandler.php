<?php declare(strict_types=1);

namespace App\Application\Command\CreateInvite;

use App\Application\Command\CommandHandler;
use App\Domain\Helpers\DomainEventBus;
use App\Domain\Model\Invite\{Invite, InviteRepository};

final class CreateInviteCommandHandler implements CommandHandler
{
    public function __construct(
        private InviteRepository $repository,
        private DomainEventBus $eventBus
    ) {
    }

    public function __invoke(CreateInviteCommand $command): void
    {
        $invite = Invite::create(
            $command->getId(),
            $command->getCode(),
            $command->getType(),
            $command->getInvitedGuests()
        );

        $this->repository->store($invite);

        $this->eventBus->publish(new InviteCreated($invite->getAggregateId()->toString()));
    }
}
