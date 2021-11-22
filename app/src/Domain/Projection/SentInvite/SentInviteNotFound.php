<?php declare(strict_types=1);

namespace App\Domain\Projection\SentInvite;

final class SentInviteNotFound extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf("Unable to find sent invite with id '%s'", $id));
    }
}
