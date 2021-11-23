<?php declare(strict_types=1);

namespace App\Application\Command\CreateInvite;

use App\Application\Command\Command;
use App\Domain\Model\Invite\Guest\GuestId;
use App\Domain\Model\Invite\Guest\GuestName;
use App\Domain\Model\Invite\Guest\InvitedGuest;
use App\Domain\Model\Invite\InviteCode;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use App\Domain\Model\Shared\GuestType;

/** @psalm-immutable */
final class CreateInviteCommand implements Command
{
    private InviteId $id;
    private InviteCode $code;
    private InviteType $type;
    /** @var InvitedGuest[] */
    private array $invitedGuests;

    public function __construct(string $type, array $invitedGuests)
    {
        $this->id = InviteId::generate();
        $this->code = InviteCode::generate();
        $this->type = InviteType::fromString($type);
        /** @psalm-suppress ImpureFunctionCall */
        $this->invitedGuests = \array_map(
            fn (array $guest) => InvitedGuest::createForInvite(
                $this->type,
                GuestId::generate(),
                GuestType::fromString($guest['type']),
                GuestName::fromString($guest['name'])
            ),
            $invitedGuests
        );
    }

    public function getId(): InviteId
    {
        return $this->id;
    }

    public function getCode(): InviteCode
    {
        return $this->code;
    }

    public function getType(): InviteType
    {
        return $this->type;
    }

    /** @return InvitedGuest[] */
    public function getInvitedGuests(): array
    {
        return $this->invitedGuests;
    }
}
