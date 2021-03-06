<?php declare(strict_types=1);

namespace App\Domain\Model\Invite\Event;

use App\Domain\Helpers\AggregateEvent;
use App\Domain\Helpers\AggregateName;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\Invite\Guest\AttendingGuest;
use App\Domain\Model\Invite\Invite;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\SongChoice;

/** @psalm-immutable */
final class InviteWasSubmitted implements AggregateEvent
{
    public const EVENT_NAME = 'invite.submitted';

    /**
     * @param AttendingGuest[] $attendingGuests
     * @param SongChoice[]     $songChoices
     */
    public function __construct(
        private InviteId $id,
        private AggregateVersion $aggregateVersion,
        private array $attendingGuests,
        private array $songChoices,
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

    /** @return AttendingGuest[] */
    public function getAttendingGuests(): array
    {
        return $this->attendingGuests;
    }

    /** @return SongChoice[] */
    public function getSongChoices(): array
    {
        return $this->songChoices;
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
            'attendingGuests' => \array_map(static fn (AttendingGuest $guest) => $guest->toArray(), $this->attendingGuests),
            'songChoices' => \array_map(static fn (SongChoice $choice) => $choice->toArray(), $this->songChoices),
            'occurredAt' => \datetime_timestamp($this->occurredAt),
        ]);
    }

    public static function deserialize(string $serialized): self
    {
        $event = \json_decode_array($serialized);

        return new self(
            InviteId::fromString($event['id']),
            AggregateVersion::fromInt($event['aggregateVersion']),
            \array_map(static fn (array $guest) => AttendingGuest::fromArray($guest), $event['attendingGuests']),
            \array_map(static fn (array $choice) => SongChoice::fromArray($choice), $event['songChoices']),
            new \DateTimeImmutable($event['occurredAt'])
        );
    }
}
