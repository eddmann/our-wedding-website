<?php declare(strict_types=1);

namespace App\Domain\Model\Shared;

use App\Domain\Helpers\AggregateEvent;
use App\Domain\Model\FoodChoice\Events\FoodChoiceWasCreated;
use App\Domain\Model\Invite\Events\InviteWasAuthenticated;
use App\Domain\Model\Invite\Events\InviteWasCreated;
use App\Domain\Model\Invite\Events\InviteWasSubmitted;

final class AggregateEventFactory
{
    public static function fromSerialized(string $eventName, string $eventData): AggregateEvent
    {
        return match ($eventName) {
            FoodChoiceWasCreated::EVENT_NAME => FoodChoiceWasCreated::deserialize($eventData),
            InviteWasCreated::EVENT_NAME => InviteWasCreated::deserialize($eventData),
            InviteWasSubmitted::EVENT_NAME => InviteWasSubmitted::deserialize($eventData),
            InviteWasAuthenticated::EVENT_NAME => InviteWasAuthenticated::deserialize($eventData),
            default => throw new \DomainException(\sprintf("Unable to build event '%s'", $eventName)),
        };
    }
}
