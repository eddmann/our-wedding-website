<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedAttendingGuest;

final class SubmittedAttendingGuestNotFound extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf("Unable to find submitted attending guest with id '%s'", $id));
    }
}
