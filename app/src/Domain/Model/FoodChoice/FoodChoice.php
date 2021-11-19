<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice;

use App\Domain\Helpers\{Aggregate, AggregateName};
use App\Domain\Model\FoodChoice\Events\FoodChoiceWasCreated;
use App\Domain\Model\Shared\GuestType;

final class FoodChoice extends Aggregate
{
    public const AGGREGATE_NAME = 'food_choice';

    /** @psalm-suppress PropertyNotSetInConstructor  */
    private FoodChoiceId $id;
    /** @psalm-suppress PropertyNotSetInConstructor  */
    private GuestType $guestType;
    /** @psalm-suppress PropertyNotSetInConstructor  */
    private FoodCourse $course;
    /** @psalm-suppress PropertyNotSetInConstructor  */
    private FoodChoiceName $name;

    public function getAggregateName(): AggregateName
    {
        return AggregateName::fromString(self::AGGREGATE_NAME);
    }

    public function getAggregateId(): FoodChoiceId
    {
        return $this->id;
    }

    public function getGuestType(): GuestType
    {
        return $this->guestType;
    }

    public function getCourse(): FoodCourse
    {
        return $this->course;
    }

    public function getName(): FoodChoiceName
    {
        return $this->name;
    }

    public static function create(
        FoodChoiceId $id,
        GuestType $guestType,
        FoodCourse $course,
        FoodChoiceName $name
    ): self {
        $choice = new self();

        $choice->raise(
            new FoodChoiceWasCreated(
                $id,
                $choice->getAggregateVersion(),
                $guestType,
                $course,
                $name,
                new \DateTimeImmutable()
            )
        );

        return $choice;
    }

    protected function applyFoodChoiceWasCreated(FoodChoiceWasCreated $event): void
    {
        $this->id = $event->getAggregateId();
        $this->guestType = $event->getGuestType();
        $this->course = $event->getCourse();
        $this->name = $event->getName();
    }
}
