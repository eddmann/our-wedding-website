<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedAttendingGuest;

final class SubmittedAttendingGuest
{
    public function __construct(
        private string $id,
        private string $inviteId,
        private string $inviteType,
        private string $guestType,
        private string $name,
        private array $chosenFoodChoices
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInviteId(): string
    {
        return $this->inviteId;
    }

    public function getInviteType(): string
    {
        return $this->inviteType;
    }

    public function getGuestType(): string
    {
        return $this->guestType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array{starterId: string, mainId: string, dessertId: string} */
    public function getChosenFoodChoices(): array
    {
        return $this->chosenFoodChoices;
    }
}
