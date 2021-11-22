<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedAttendingGuest;

interface SubmittedAttendingGuestRepository
{
    public function store(SubmittedAttendingGuest $guest): void;

    /** @throws SubmittedAttendingGuestNotFound */
    public function get(string $id): SubmittedAttendingGuest;

    /** @return SubmittedAttendingGuest[] */
    public function getByInviteId(string $inviteId): array;

    /** @return SubmittedAttendingGuest[] */
    public function all(): array;
}
