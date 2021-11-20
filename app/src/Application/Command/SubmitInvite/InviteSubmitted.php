<?php declare(strict_types=1);

namespace App\Application\Command\SubmitInvite;

use App\Domain\Helpers\DomainEvent;

/** @psalm-immutable */
final class InviteSubmitted implements DomainEvent
{
    public function __construct(
        public readonly string $id,
        public readonly array $guests
    ) {
    }
}
