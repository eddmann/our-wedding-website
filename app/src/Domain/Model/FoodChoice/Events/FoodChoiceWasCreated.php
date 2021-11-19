<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice\Events;

use App\Domain\Helpers\{AggregateEvent, AggregateName, AggregateVersion};
use App\Domain\Model\FoodChoice\{FoodChoiceId, FoodChoiceName, FoodCourse};
use App\Domain\Model\Shared\GuestType;

/** @psalm-immutable */
final class FoodChoiceWasCreated implements AggregateEvent
{
    private AggregateName $aggregateName;
    private FoodChoiceId $id;
    private AggregateVersion $aggregateVersion;
    private GuestType $guestType;
    private FoodCourse $course;
    private FoodChoiceName $name;
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        FoodChoiceId $id,
        AggregateVersion $aggregateVersion,
        GuestType $guestType,
        FoodCourse $course,
        FoodChoiceName $name,
        \DateTimeImmutable $occurredAt
    ) {
        $this->aggregateName = AggregateName::fromString('food_choice');
        $this->id = $id;
        $this->aggregateVersion = $aggregateVersion;
        $this->guestType = $guestType;
        $this->course = $course;
        $this->name = $name;
        $this->occurredAt = $occurredAt;
    }

    public function getAggregateName(): AggregateName
    {
        return $this->aggregateName;
    }

    public function getAggregateId(): FoodChoiceId
    {
        return $this->id;
    }

    public function getAggregateVersion(): AggregateVersion
    {
        return $this->aggregateVersion;
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

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function serialize(): string
    {
        return \json_encode_array([
            'aggregateVersion' => $this->aggregateVersion->toInt(),
            'id' => (string) $this->id,
            'guestType' => $this->guestType->toString(),
            'course' => $this->course->toString(),
            'name' => (string) $this->name,
            'occurredAt' => \datetime_timestamp($this->occurredAt),
        ]);
    }

    /** @psalm-suppress UndefinedMethod */
    public static function deserialize(string $serialized): AggregateEvent
    {
        $event = \json_decode_array($serialized);

        return new self(
            FoodChoiceId::fromString($event['id']),
            AggregateVersion::fromInt($event['aggregateVersion']),
            GuestType::fromString($event['guestType']),
            FoodCourse::fromString($event['course']),
            FoodChoiceName::fromString($event['name']),
            new \DateTimeImmutable($event['occurredAt'])
        );
    }
}
