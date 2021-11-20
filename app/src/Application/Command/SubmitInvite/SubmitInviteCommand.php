<?php declare(strict_types=1);

namespace App\Application\Command\SubmitInvite;

use App\Application\Command\Command;
use App\Domain\Model\Invite\Guest\ChosenFoodChoices;
use App\Domain\Model\Invite\{InviteId, SongChoice};

/** @psalm-immutable */
final class SubmitInviteCommand implements Command
{
    private InviteId $id;
    /** @var ChosenFoodChoices[] */
    private array $chosenFoodChoices;
    /** @var SongChoice[] */
    private array $songChoices;

    /** @psalm-suppress ImpureFunctionCall */
    public function __construct(
        string $id,
        array $chosenFoodChoices,
        array $songChoices
    ) {
        $this->id = InviteId::fromString($id);
        $this->chosenFoodChoices = \array_map(static fn (array $choices) => ChosenFoodChoices::fromArray($choices), $chosenFoodChoices);
        $this->songChoices = \array_map(static fn (array $song) => SongChoice::fromString($song['artist'], $song['track']), $songChoices);
    }

    public function getId(): InviteId
    {
        return $this->id;
    }

    /** @return ChosenFoodChoices[] */
    public function getChosenFoodChoices(): array
    {
        return $this->chosenFoodChoices;
    }

    /** @return SongChoice[] */
    public function getSongChoices(): array
    {
        return $this->songChoices;
    }
}
