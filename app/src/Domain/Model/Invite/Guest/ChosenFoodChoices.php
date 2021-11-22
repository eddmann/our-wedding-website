<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Guest;

use App\Domain\Model\FoodChoice\FoodChoiceId;

/** @psalm-immutable */
final class ChosenFoodChoices
{
    private function __construct(
        private ?FoodChoiceId $starterId,
        private ?FoodChoiceId $mainId,
        private ?FoodChoiceId $dessertId,
        private string $dietaryRequirements
    ) {
    }

    public function getStarterId(): ?FoodChoiceId
    {
        return $this->starterId;
    }

    public function getMainId(): ?FoodChoiceId
    {
        return $this->mainId;
    }

    public function getDessertId(): ?FoodChoiceId
    {
        return $this->dessertId;
    }

    public function getDietaryRequirements(): string
    {
        return $this->dietaryRequirements;
    }

    /** @psalm-mutation-free */
    public function toArray(): array
    {
        return [
            'starterId' => $this->getStarterId()?->toString(),
            'mainId' => $this->getMainId()?->toString(),
            'dessertId' => $this->getDessertId()?->toString(),
            'dietaryRequirements' => $this->getDietaryRequirements(),
        ];
    }

    /** @psalm-mutation-free */
    public function isNone(): bool
    {
        return
            $this->starterId === null
            && $this->mainId === null
            && $this->dessertId === null
            && $this->dietaryRequirements === '';
    }

    public static function none(): self
    {
        return new self(null, null, null, '');
    }

    public static function fromArray(array $choices): self
    {
        return new self(
            isset($choices['starterId']) ? FoodChoiceId::fromString($choices['starterId']) : null,
            isset($choices['mainId']) ? FoodChoiceId::fromString($choices['mainId']) : null,
            isset($choices['dessertId']) ? FoodChoiceId::fromString($choices['dessertId']) : null,
            $choices['dietaryRequirements'] ?? ''
        );
    }
}
