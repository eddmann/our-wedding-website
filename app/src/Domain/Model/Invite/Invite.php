<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

use App\Domain\Helpers\Aggregate;
use App\Domain\Helpers\AggregateName;
use App\Domain\Model\Invite\Guest\AttendingGuest;
use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\ChosenFoodChoiceValidator;
use App\Domain\Model\Invite\Guest\InvitedGuest;

/** @psalm-suppress PropertyNotSetInConstructor */
final class Invite extends Aggregate
{
    public const AGGREGATE_NAME = 'invite';
    private const MAX_SONG_CHOICES = 2;

    private InviteId $id;
    private InviteCode $inviteCode;
    private InviteType $inviteType;
    /**
     * @psalm-var list<InvitedGuest>
     * @var InvitedGuest[]
     */
    private array $invitedGuests;
    /**
     * @psalm-var list<AttendingGuest>|null
     * @var AttendingGuest[]|null
     */
    private ?array $attendingGuests = null;
    /**
     * @psalm-var list<SongChoice>|null
     * @var SongChoice[]|null
     */
    private ?array $songChoices = null;
    private ?\DateTimeImmutable $submittedAt = null;
    private ?\DateTimeImmutable $lastAuthenticatedAt = null;

    public function getAggregateName(): AggregateName
    {
        return AggregateName::fromString(self::AGGREGATE_NAME);
    }

    public function getAggregateId(): InviteId
    {
        return $this->id;
    }

    public function getInviteCode(): InviteCode
    {
        return $this->inviteCode;
    }

    public function getInviteType(): InviteType
    {
        return $this->inviteType;
    }

    /**
     * @psalm-return list<InvitedGuest>
     * @return InvitedGuest[]
     */
    public function getInvitedGuests(): array
    {
        return $this->invitedGuests;
    }

    /**
     * @psalm-return list<AttendingGuest>|null
     * @return AttendingGuest[]|null
     */
    public function getAttendingGuests(): ?array
    {
        return $this->attendingGuests;
    }

    /**
     * @psalm-return list<SongChoice>|null
     * @return SongChoice[]|null
     */
    public function getSongChoices(): ?array
    {
        return $this->songChoices;
    }

    public function isSubmitted(): bool
    {
        return $this->submittedAt !== null;
    }

    public function getLastAuthenticatedAt(): ?\DateTimeImmutable
    {
        return $this->lastAuthenticatedAt;
    }

    /**
     * @psalm-param list<InvitedGuest> $invitedGuests
     * @param InvitedGuest[] $invitedGuests
     */
    public static function create(
        InviteId $id,
        InviteCode $code,
        InviteType $inviteType,
        array $invitedGuests
    ): self {
        $invite = new self();

        if (empty($invitedGuests)) {
            throw new \DomainException('An invite must have at least one guest');
        }

        $invite->raise(
            new Event\InviteWasCreated(
                $id,
                $invite->getAggregateVersion(),
                $code,
                $inviteType,
                $invitedGuests,
                new \DateTimeImmutable()
            )
        );

        return $invite;
    }

    public function authenticate(InviteAuthenticator $authenticator): void
    {
        $authenticator->login($this->id, $this->inviteType);

        $this->raise(
            new Event\InviteWasAuthenticated(
                $this->getAggregateId(),
                $this->getAggregateVersion(),
                new \DateTimeImmutable()
            )
        );
    }

    /**
     * @param ChosenFoodChoices[] $foodChoices
     * @param SongChoice[]        $songChoices
     */
    public function submit(
        ChosenFoodChoiceValidator $foodChoiceValidator,
        array $foodChoices,
        array $songChoices
    ): void {
        if (null === $this->getLastAuthenticatedAt()) {
            throw new \DomainException('Invite must be authenticated for submission');
        }

        if ($this->isSubmitted()) {
            throw new \DomainException('This invite has already been submitted');
        }

        if (\count($songChoices) > self::MAX_SONG_CHOICES) {
            throw new \DomainException('Only two song choices allowed per invite');
        }

        $attendingGuests = \array_reduce(
            $this->invitedGuests,
            static fn (array $attending, InvitedGuest $guest) => isset($foodChoices[$guest->getId()->toString()])
                ? [...$attending, $guest->submit($foodChoiceValidator, $foodChoices[$guest->getId()->toString()])]
                : $attending,
            []
        );

        $this->raise(
            new Event\InviteWasSubmitted(
                $this->getAggregateId(),
                $this->getAggregateVersion(),
                $attendingGuests,
                $songChoices,
                new \DateTimeImmutable()
            )
        );
    }

    protected function applyInviteWasCreated(Event\InviteWasCreated $event): void
    {
        $this->id = $event->getAggregateId();
        $this->inviteCode = $event->getInviteCode();
        $this->inviteType = $event->getInviteType();
        $this->invitedGuests = $event->getInvitedGuests();
    }

    protected function applyInviteWasAuthenticated(Event\InviteWasAuthenticated $event): void
    {
        $this->lastAuthenticatedAt = $event->getOccurredAt();
    }

    protected function applyInviteWasSubmitted(Event\InviteWasSubmitted $event): void
    {
        $this->submittedAt = $event->getOccurredAt();
        $this->attendingGuests = $event->getAttendingGuests();
        $this->songChoices = $event->getSongChoices();
    }
}
