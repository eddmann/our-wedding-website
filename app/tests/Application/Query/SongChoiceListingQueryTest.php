<?php declare(strict_types=1);

namespace App\Tests\Application\Query;

use App\Application\Query\SongChoiceListingQuery;
use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateVersion;
use App\Domain\Model\Invite\Event\InviteWasCreated;
use App\Domain\Model\Invite\Event\InviteWasSubmitted;
use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Invite\SongChoice;
use App\Domain\Model\Shared\GuestType;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceProjector;
use App\Tests\Doubles\ChosenFoodChoiceValidatorStub;
use App\Tests\Doubles\InMemorySubmittedSongChoiceRepository;
use PHPUnit\Framework\TestCase;

final class SongChoiceListingQueryTest extends TestCase
{
    private SubmittedSongChoiceProjector $songChoiceProjector;
    private SongChoiceListingQuery $query;

    protected function setUp(): void
    {
        $this->songChoiceProjector = new SubmittedSongChoiceProjector(
            $songChoiceRepository = new InMemorySubmittedSongChoiceRepository()
        );
        $this->query = new SongChoiceListingQuery($songChoiceRepository);
    }

    public function test_it_lists_submitted_song_choices(): void
    {
        $events = AggregateEvents::make()
            ->add(
                new InviteWasCreated(
                    $inviteId = InviteId::generate(),
                    $version = AggregateVersion::zero(),
                    InviteCode::generate(),
                    $inviteType = InviteType::Day,
                    [
                        $guest = InvitedGuest::createForInvite($inviteType, GuestId::generate(), GuestType::Adult, GuestName::fromString('Adult Name')),
                    ],
                    new \DateTimeImmutable()
                )
            )
            ->add(
                new InviteWasSubmitted(
                    $inviteId,
                    $version->next(),
                    [
                        $guest->submit(
                            new ChosenFoodChoiceValidatorStub(),
                            ChosenFoodChoices::fromArray([
                                'starterId' => 'e96ddf7f-0897-48f5-9534-81d596e3542f',
                                'mainId' => '2ff8ec3a-e91b-4734-9a4a-bed588d6894f',
                                'dessertId' => 'd53efc4d-83f3-41c3-9723-93961e245300',
                            ])
                        ),
                    ],
                    [SongChoice::fromString('ARTIST_NAME', 'TRACK_NAME')],
                    $submittedAt = new \DateTimeImmutable()
                )
            );

        ($this->songChoiceProjector)($events);

        self::assertEquals([
            [
                'artist' => 'ARTIST_NAME',
                'track' => 'TRACK_NAME',
                'submittedAt' => $submittedAt->format('Y-m-d H:i:s'),
            ],
        ], $this->query->query());
    }
}
