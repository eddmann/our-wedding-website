<?php declare(strict_types=1);

namespace App\Domain\Model\Invite;

final class InviteNotFound extends \DomainException
{
    public function __construct(InviteId $id)
    {
        parent::__construct(\sprintf("Unable to find invite with id '%s'", $id->toString()));
    }
}
