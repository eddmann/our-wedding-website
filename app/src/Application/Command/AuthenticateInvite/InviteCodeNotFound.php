<?php declare(strict_types=1);

namespace App\Application\Command\AuthenticateInvite;

use App\Domain\Model\Invite\InviteCode;

final class InviteCodeNotFound extends \DomainException
{
    public function __construct(InviteCode $code)
    {
        parent::__construct(\sprintf("Unable to find invite with code '%s'", $code->toString()));
    }
}
