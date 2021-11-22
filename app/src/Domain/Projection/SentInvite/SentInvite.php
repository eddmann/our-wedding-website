<?php declare(strict_types=1);

namespace App\Domain\Projection\SentInvite;

final class SentInvite
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_SUBMITTED = 'submitted';

    private string $id;
    private string $code;
    private string $type;
    private array $invitedGuests;
    private ?\DateTimeImmutable $submittedAt;
    private ?\DateTimeImmutable $lastAuthenticatedAt;

    public function __construct(
        string $id,
        string $code,
        string $type,
        array $invitedGuests,
        ?\DateTimeImmutable $submittedAt = null,
        ?\DateTimeImmutable $lastAuthenticatedAt = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->type = $type;
        $this->invitedGuests = $invitedGuests;
        $this->submittedAt = $submittedAt;
        $this->lastAuthenticatedAt = $lastAuthenticatedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getInvitedGuests(): array
    {
        return $this->invitedGuests;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function getLastAuthenticatedAt(): ?\DateTimeImmutable
    {
        return $this->lastAuthenticatedAt;
    }

    public function getStatus(): string
    {
        return $this->isSubmitted() ? self::STATUS_SUBMITTED : self::STATUS_PENDING;
    }

    public function isSubmitted(): bool
    {
        return $this->submittedAt !== null;
    }

    public function submitted(\DateTimeImmutable $occurredAt): void
    {
        $this->submittedAt = $occurredAt;
    }

    public function authenticated(\DateTimeImmutable $occurredAt): void
    {
        $this->lastAuthenticatedAt = $occurredAt;
    }
}
