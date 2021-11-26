<?php declare(strict_types=1);

namespace App\Tests\Application\Query;

use App\Application\Query\InviteListingQuery;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\Invite\Event\InviteWasAuthenticated;
use App\Domain\Model\Invite\Event\InviteWasCreated;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\SentInvite\SentInviteProjector;
use App\Tests\Doubles\InMemorySentInviteRepository;
use PHPUnit\Framework\TestCase;

final class InviteListingQueryTest extends TestCase
{
    private SentInviteProjector $sentInviteProjector;
    private InviteListingQuery $query;

    protected function setUp(): void
    {
        $this->sentInviteProjector = new SentInviteProjector($sentInviteRepository = new InMemorySentInviteRepository());
        $this->query = new InviteListingQuery($sentInviteRepository);
    }

    public function test_it_lists_present_invites(): void
    {
        $pendingEveningInvite = $this->createPendingEveningInvite();
        $submittedEveningInvite = $this->createSubmittedEveningInvite();

        self::assertEquals([
            [
                'id' => $pendingEveningInvite['id'],
                'type' => 'evening',
                'status' => 'pending',
                'code' => $pendingEveningInvite['code'],
                'invitedGuests' => ['Adult Name'],
                'submittedAt' => null,
                'lastAuthenticatedAt' => null,
            ],
            [
                'id' => $submittedEveningInvite['id'],
                'type' => 'evening',
                'status' => 'submitted',
                'code' => $submittedEveningInvite['code'],
                'invitedGuests' => ['Adult Name'],
                'submittedAt' => $submittedEveningInvite['submittedAt'],
                'lastAuthenticatedAt' => $submittedEveningInvite['authenticatedAt'],
            ],
        ], $this->query->query());
    }

    private function createPendingEveningInvite(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    AggregateVersion::zero(),
                    $inviteCode = InviteCode::generate(),
                    $inviteType = InviteType::Evening,
                    [
                        InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            );

        ($this->sentInviteProjector)($events);

        return [
            'id' => $inviteId->toString(),
            'code' => $inviteCode->toString(),
        ];
    }

    private function createSubmittedEveningInvite(): array
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    $inviteCode = InviteCode::generate(),
                    $inviteType = InviteType::Evening,
                    [
                        InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasAuthenticated(
                    $inviteId,
                    $version = $version->next(),
                    $authenticatedAt = new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [],
                    [],
                    $submittedAt = new \DateTimeImmutable()
                )
            );

        ($this->sentInviteProjector)($events);

        return [
            'id' => $inviteId->toString(),
            'code' => $inviteCode->toString(),
            'authenticatedAt' => $authenticatedAt->format('Y-m-d H:i:s'),
            'submittedAt' => $submittedAt->format('Y-m-d H:i:s'),
        ];
    }
}
