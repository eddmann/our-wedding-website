<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Model\Shared\GuestType;

final class ChosenFoodChoiceValidatorStub implements ChosenFoodChoiceValidator
{
    private bool $isPassing = true;

    public function passing(): void
    {
        $this->isPassing = true;
    }

    public function failing(): void
    {
        $this->isPassing = false;
    }

    public function isValid(GuestType $type, ChosenFoodChoices $choices): bool
    {
        return $this->isPassing;
    }
}
