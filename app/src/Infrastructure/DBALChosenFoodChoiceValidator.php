<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Invite\Guest\{ChosenFoodChoiceValidator, ChosenFoodChoices};
use App\Domain\Model\Shared\GuestType;

final class DBALChosenFoodChoiceValidator implements ChosenFoodChoiceValidator
{
    /** @psalm-mutation-free */
    public function isValid(GuestType $type, ChosenFoodChoices $choices): bool
    {
        return true;
    }
}
