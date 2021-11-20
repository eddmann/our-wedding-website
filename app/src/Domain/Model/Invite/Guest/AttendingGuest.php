<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Guest;

use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;

/** @psalm-immutable */
final class AttendingGuest
{
    public function __construct(
        private GuestId $id,
        private InviteType $inviteType,
        private GuestType $guestType,
        private GuestName $name,
        private ChosenFoodChoices $chosenFoodChoices
    ) {
    }

    public function getId(): GuestId
    {
        return $this->id;
    }

    public function getInviteType(): InviteType
    {
        return $this->inviteType;
    }

    public function getGuestType(): GuestType
    {
        return $this->guestType;
    }

    public function getName(): GuestName
    {
        return $this->name;
    }

    public function getChosenFoodChoices(): ChosenFoodChoices
    {
        return $this->chosenFoodChoices;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'inviteType' => $this->inviteType->toString(),
            'guestType' => $this->guestType->toString(),
            'name' => $this->name->toString(),
            'chosenFoodChoices' => $this->chosenFoodChoices->toArray(),
        ];
    }

    public static function fromArray(array $guest): self
    {
        return new self(
            GuestId::fromString($guest['id']),
            InviteType::fromString($guest['inviteType']),
            GuestType::fromString($guest['guestType']),
            GuestName::fromString($guest['name']),
            ChosenFoodChoices::fromArray($guest['chosenFoodChoices'])
        );
    }
}
