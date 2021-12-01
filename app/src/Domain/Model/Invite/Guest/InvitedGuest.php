<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Guest;

use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;

/** @psalm-immutable */
final class InvitedGuest
{
    private function __construct(
        private GuestId $id,
        private InviteType $inviteType,
        private GuestType $guestType,
        private GuestName $name,
        private bool $hasFoodChoices
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

    public function hasFoodChoices(): bool
    {
        return $this->hasFoodChoices;
    }

    public static function createForInvite(
        InviteType $inviteType,
        GuestId $id,
        GuestType $guestType,
        GuestName $name
    ): self {
        $hasFoodChoices = InviteType::Day->equals($inviteType) && ! GuestType::Baby->equals($guestType);

        return new self($id, $inviteType, $guestType, $name, $hasFoodChoices);
    }

    public function submit(
        ChosenFoodChoiceValidator $foodChoiceValidator,
        ChosenFoodChoices $foodChoices
    ): AttendingGuest {
        if ($this->hasFoodChoices === $foodChoices->isNone()) {
            throw new \DomainException(
                \sprintf(
                    "%s's food choices do not meet the specified requirements",
                    $this->name->toString()
                )
            );
        }

        if ($this->hasFoodChoices && ! $foodChoiceValidator->isValid($this->guestType, $foodChoices)) {
            throw new \DomainException(
                \sprintf(
                    "%s's food choices do not meet the %s type requirements",
                    $this->name->toString(),
                    $this->guestType->toString()
                )
            );
        }

        return new AttendingGuest(
            $this->id,
            $this->inviteType,
            $this->guestType,
            $this->name,
            $foodChoices
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'inviteType' => $this->inviteType->toString(),
            'guestType' => $this->guestType->toString(),
            'name' => $this->name->toString(),
            'hasFoodChoices' => $this->hasFoodChoices,
        ];
    }

    public static function fromArray(array $guest): self
    {
        return new self(
            GuestId::fromString($guest['id']),
            InviteType::fromString($guest['inviteType']),
            GuestType::fromString($guest['guestType']),
            GuestName::fromString($guest['name']),
            $guest['hasFoodChoices']
        );
    }
}
