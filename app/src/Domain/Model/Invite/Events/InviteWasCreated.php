<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Events;

use App\Domain\Helpers\AggregateEvent;
use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;

/** @psalm-immutable */
final class InviteWasCreated implements AggregateEvent
{
    public const EVENT_NAME = 'invite.created';

    /** @param InvitedGuest[] $invitedGuests */
    public function __construct(
        private InviteId $id,
        private AggregateVersion $aggregateVersion,
        private InviteCode $inviteCode,
        private InviteType $inviteType,
        private array $invitedGuests,
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

    public function getInviteCode(): InviteCode
    {
        return $this->inviteCode;
    }

    public function getInviteType(): InviteType
    {
        return $this->inviteType;
    }

    /** @return InvitedGuest[] */
    public function getInvitedGuests(): array
    {
        return $this->invitedGuests;
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
            'inviteCode' => $this->inviteCode->toString(),
            'inviteType' => $this->inviteType->toString(),
            'invitedGuests' => \array_map(static fn (InvitedGuest $guest) => $guest->toArray(), $this->invitedGuests),
            'occurredAt' => \datetime_timestamp($this->occurredAt),
        ]);
    }

    public static function deserialize(string $serialized): self
    {
        $event = \json_decode_array($serialized);

        return new self(
            InviteId::fromString($event['id']),
            AggregateVersion::fromInt($event['aggregateVersion']),
            InviteCode::fromString($event['inviteCode']),
            InviteType::fromString($event['inviteType']),
            \array_map(static fn (array $guest) => InvitedGuest::fromArray($guest), $event['invitedGuests']),
            new \DateTimeImmutable($event['occurredAt'])
        );
    }
}
