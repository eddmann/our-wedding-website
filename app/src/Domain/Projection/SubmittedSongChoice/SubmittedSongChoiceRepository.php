<?php declare(strict_types=1);

namespace App\Domain\Projection\SubmittedSongChoice;

interface SubmittedSongChoiceRepository
{
    public function store(SubmittedSongChoice $choice): void;

    /** @return SubmittedSongChoice[] */
    public function all(): array;
}
