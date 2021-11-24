<?php declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Projection\SentInvite\SentInvite;
use App\Domain\Projection\SentInvite\SentInviteRepository;

final class InviteListingQuery
{
    public function __construct(private SentInviteRepository $sentInviteRepository)
    {
    }

    public function query(): array
    {
        return \array_map(
            static fn (SentInvite $invite) => [
                'id' => $invite->getId(),
                'type' => $invite->getType(),
                'code' => $invite->getCode(),
                'invitedGuests' => \array_column($invite->getInvitedGuests(), 'name'),
                'status' => $invite->getStatus(),
                'submittedAt' => $invite->getSubmittedAt()?->format('Y-m-d H:i:s'),
                'lastAuthenticatedAt' => $invite->getLastAuthenticatedAt()?->format('Y-m-d H:i:s'),
            ],
            $this->sentInviteRepository->all()
        );
    }
}
