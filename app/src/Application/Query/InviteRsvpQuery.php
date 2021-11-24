<?php declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoice;
use App\Domain\Projection\AvailableFoodChoice\AvailableFoodChoiceRepository;
use App\Domain\Projection\SentInvite\SentInviteRepository;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;

final class InviteRsvpQuery
{
    public function __construct(
        private SentInviteRepository $sentInviteRepository,
        private AvailableFoodChoiceRepository $foodChoiceRepository,
        private SubmittedAttendingGuestRepository $attendingGuestRepository
    ) {
    }

    public function query(string $id): array
    {
        $invite = $this->sentInviteRepository->get($id);

        if ($invite->isSubmitted()) {
            $attendingGuests = $this->attendingGuestRepository->getByInviteId($id);

            return [
                'status' => $invite->getStatus(),
                'type' => $invite->getType(),
                'guests' => \array_map([$this, 'mapAttendingGuest'], $attendingGuests),
            ];
        }

        return [
            'status' => $invite->getStatus(),
            'type' => $invite->getType(),
            'guests' => \array_map([$this, 'mapInvitedGuest'], $invite->getInvitedGuests()),
        ];
    }

    private function mapInvitedGuest(array $guest): array
    {
        return [
            'id' => $guest['id'],
            'name' => $guest['name'],
            'foodChoices' => $this->toAvailableFoodChoices($guest['guestType'], $guest['hasFoodChoices']),
        ];
    }

    private function toAvailableFoodChoices(string $guestType, bool $hasFoodChoices): array
    {
        if (! $hasFoodChoices) {
            return [];
        }

        $foodCourses = $this->foodChoiceRepository->getCoursesByGuestType($guestType);

        return \array_map(
            static fn (array $course) => \array_map(
                static fn (AvailableFoodChoice $choice) => [
                    'id' => $choice->getId(),
                    'name' => $choice->getName(),
                ],
                $course
            ),
            $foodCourses
        );
    }

    private function mapAttendingGuest(SubmittedAttendingGuest $guest): array
    {
        return [
            'id' => $guest->getId(),
            'name' => $guest->getName(),
            'chosenFoodChoices' => $this->toFoodChoiceNames($guest->getChosenFoodChoices()),
        ];
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
}
