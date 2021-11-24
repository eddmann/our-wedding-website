<?php declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;

final class AttendingGuestListingQuery
{
    public function __construct(
        private SubmittedAttendingGuestRepository $attendingGuestRepository,
        private AvailableFoodChoiceRepository $foodChoiceRepository
    ) {
    }

    public function query(): array
    {
        return \array_map(
            fn (SubmittedAttendingGuest $guest) => [
                'id' => $guest->getId(),
                'inviteType' => $guest->getInviteType(),
                'guestType' => $guest->getGuestType(),
                'name' => $guest->getName(),
                'chosenFoodChoices' => $this->toFoodChoiceNames($guest->getChosenFoodChoices()),
                'dietaryRequirements' => $this->toDietaryRequirements($guest->getChosenFoodChoices()),
            ],
            $this->attendingGuestRepository->all()
        );
    }

    private function toFoodChoiceNames(array $chosenFoodChoices): array
    {
        return \array_values(
            \array_filter([
                $chosenFoodChoices['starterId'] ? $this->foodChoiceRepository->get($chosenFoodChoices['starterId'])->getName() : null,
                $chosenFoodChoices['mainId'] ? $this->foodChoiceRepository->get($chosenFoodChoices['mainId'])->getName() : null,
                $chosenFoodChoices['dessertId'] ? $this->foodChoiceRepository->get($chosenFoodChoices['dessertId'])->getName() : null,
            ])
        );
    }

    private function toDietaryRequirements(array $chosenFoodChoices): string
    {
        return $chosenFoodChoices['dietaryRequirements'] ?? '';
    }
}
