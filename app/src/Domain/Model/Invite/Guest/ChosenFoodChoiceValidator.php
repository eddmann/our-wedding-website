<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Guest;

use App\Domain\Model\Shared\GuestType;

interface ChosenFoodChoiceValidator
{
    /** @psalm-mutation-free */
    public function isValid(GuestType $type, ChosenFoodChoices $choices): bool;
}
