<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Events;

use App\Domain\Helpers\{AggregateEvent, AggregateName, AggregateVersion};
use App\Domain\Model\Invite\{Invite, InviteId};

/** @psalm-immutable */
final class InviteWasAuthenticated implements AggregateEvent
{
    public const EVENT_NAME = 'invite.authenticated';

    public function __construct(
        private InviteId $id,
        private AggregateVersion $aggregateVersion,
        private \DateTimeImmutable $occurredAt
    ) {
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getAggregateName(): AggregateName
    {
        return AggregateName::fromString(Invite::AGGREGATE_NAME);
    }

    public function getAggregateId(): InviteId
    {
        return $this->id;
    }

    public function getAggregateVersion(): AggregateVersion
    {
        return $this->aggregateVersion;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function serialize(): string
    {
        return \json_encode_array([
            'aggregateVersion' => $this->aggregateVersion->toInt(),
            'id' => $this->id->toString(),
            'occurredAt' => \datetime_timestamp($this->occurredAt),
        ]);
    }

    public static function deserialize(string $serialized): self
    {
        $event = \json_decode_array($serialized);

        return new self(
            InviteId::fromString($event['id']),
            AggregateVersion::fromInt($event['aggregateVersion']),
            new \DateTimeImmutable($event['occurredAt'])
        );
    }
}
