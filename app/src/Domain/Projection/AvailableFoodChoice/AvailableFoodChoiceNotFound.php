<?php declare(strict_types=1);

namespace App\Domain\Projection\AvailableFoodChoice;

final class AvailableFoodChoiceNotFound extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf("Unable to find available food choice with id '%s'", $id));
    }
}
