<?php declare(strict_types=1);

namespace App\Application\Command\CreateInvite;

use App\Domain\Helpers\DomainEvent;

/** @psalm-immutable */
final class InviteCreated implements DomainEvent
{
    public function __construct(public readonly string $id)
    {
    }
}
