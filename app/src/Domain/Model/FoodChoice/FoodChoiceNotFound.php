<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice;

final class FoodChoiceNotFound extends \DomainException
{
    public function __construct(FoodChoiceId $id)
    {
        parent::__construct(\sprintf("Unable to find food choice with id '%s'", $id->toString()));
    }
}
