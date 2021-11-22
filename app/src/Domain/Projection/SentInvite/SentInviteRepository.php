<?php declare(strict_types=1);

namespace App\Domain\Projection\SentInvite;

interface SentInviteRepository
{
    public function store(SentInvite $invite): void;

    /** @throws SentInviteNotFound */
    public function get(string $id): SentInvite;

    /** @return SentInvite[] */
    public function all(): array;
}
