<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoice;
use App\Domain\Projection\SubmittedSongChoice\SubmittedSongChoiceRepository;

final class InMemorySubmittedSongChoiceRepository implements SubmittedSongChoiceRepository
{
    /** @var SubmittedSongChoice[] */
    private array $choices = [];

    public function store(SubmittedSongChoice $choice): void
    {
        $this->choices[] = $choice;
    }

    /** @return SubmittedSongChoice[] */
    public function all(): array
    {
        return $this->choices;
    }
}
