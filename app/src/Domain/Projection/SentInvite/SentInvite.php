<?php declare(strict_types=1);

namespace App\Domain\Projection\SentInvite;

final class SentInvite
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_SUBMITTED = 'submitted';

    public function __construct(
        private string $id,
        private string $code,
        private string $type,
        private array $invitedGuests,
        private ?\DateTimeImmutable $submittedAt = null,
        private ?\DateTimeImmutable $lastAuthenticatedAt = null
    ) {
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
