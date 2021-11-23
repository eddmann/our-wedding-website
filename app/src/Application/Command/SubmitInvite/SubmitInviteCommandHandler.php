<?php declare(strict_types=1);

namespace App\Application\Command\SubmitInvite;

use App\Application\Command\CommandHandler;
use App\Domain\Helpers\DomainEventBus;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteRepository;

final class SubmitInviteCommandHandler implements CommandHandler
{
    public function __construct(
        private ChosenFoodChoiceValidator $foodMenuValidator,
        private InviteRepository $repository,
        private DomainEventBus $eventBus
    ) {
    }

    public function __invoke(SubmitInviteCommand $command): void
    {
        $invite = $this->repository->get($command->getId());

        $invite->submit(
            $this->foodMenuValidator,
            $command->getChosenFoodChoices(),
            $command->getSongChoices()
        );

        $this->repository->store($invite);

        $this->eventBus->publish(
            new InviteSubmitted(
                $invite->getAggregateId()->toString(),
                \array_map(
                    fn (InvitedGuest $guest) => ['name' => $guest->getName()->toString(), 'attending' => $this->isGuestAttending($invite, $guest->getId())],
                    $invite->getInvitedGuests()
                )
            )
        );
    }

    private function isGuestAttending(Invite $invite, GuestId $guestId): bool
    {
        foreach (($invite->getAttendingGuests() ?? []) as $guest) {
            if ($guest->getId()->equals($guestId)) {
                return true;
            }
        }

        return false;
    }
}
