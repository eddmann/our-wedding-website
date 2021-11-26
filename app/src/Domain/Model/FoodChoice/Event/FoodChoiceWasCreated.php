<?php declare(strict_types=1);

namespace App\Domain\Model\FoodChoice\Event;

use App\Domain\Helpers\AggregateEvent;
use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\FoodChoice\FoodChoice;
use App\Domain\Model\FoodChoice\FoodChoiceId;
use App\Domain\Model\FoodChoice\FoodChoiceName;
use App\Domain\Model\FoodChoice\FoodCourse;
use App\Domain\Model\Shared\GuestType;

/** @psalm-immutable */
final class FoodChoiceWasCreated implements AggregateEvent
{
    public const EVENT_NAME = 'food_choice.created';

    public function __construct(
        private FoodChoiceId $id,
        private AggregateVersion $aggregateVersion,
        private GuestType $guestType,
        private FoodCourse $course,
        private FoodChoiceName $name,
        private \DateTimeImmutable $occurredAt
    ) {
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getAggregateName(): AggregateName
    {
        return AggregateName::fromString(FoodChoice::AGGREGATE_NAME);
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
            'id' => $this->id->toString(),
            'aggregateVersion' => $this->aggregateVersion->toInt(),
            'guestType' => $this->guestType->toString(),
            'course' => $this->course->toString(),
            'name' => $this->name->toString(),
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
