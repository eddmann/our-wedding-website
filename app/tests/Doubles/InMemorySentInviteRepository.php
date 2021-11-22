<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Projection\SentInvite\{SentInvite, SentInviteNotFound, SentInviteRepository};

final class InMemorySentInviteRepository implements SentInviteRepository
{
    /** @var SentInvite[] */
    private array $invites = [];

    public function store(SentInvite $invite): void
    {
        $this->invites[$invite->getId()] = $invite;
    }

    public function get(string $id): SentInvite
    {
        return $this->invites[$id] ?? throw new SentInviteNotFound($id);
    }

    /** @return SentInvite[] */
    public function all(): array
    {
        return \array_values($this->invites);
    }
}
