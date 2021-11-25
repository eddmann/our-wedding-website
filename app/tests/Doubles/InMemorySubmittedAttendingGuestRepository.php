<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuest;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestNotFound;
use App\Domain\Projection\SubmittedAttendingGuest\SubmittedAttendingGuestRepository;

final class InMemorySubmittedAttendingGuestRepository implements SubmittedAttendingGuestRepository
{
    /** @var SubmittedAttendingGuest[] */
    private array $guests = [];

    public function store(SubmittedAttendingGuest $guest): void
    {
        $this->guests[$guest->getId()] = $guest;
    }

    public function get(string $id): SubmittedAttendingGuest
    {
        return $this->guests[$id] ?? throw new SubmittedAttendingGuestNotFound($id);
    }

    /** @return SubmittedAttendingGuest[] */
    public function getByInviteId(string $inviteId): array
    {
        return \array_values(
            \array_filter($this->guests, static fn (SubmittedAttendingGuest $guest) => $guest->getInviteId() === $inviteId)
        );
    }

    /** @return SubmittedAttendingGuest[] */
    public function all(): array
    {
        return \array_values($this->guests);
    }
}
