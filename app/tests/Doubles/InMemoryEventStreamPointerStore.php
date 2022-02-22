<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\EventStreamPointer;
use App\Domain\Helpers\EventStreamPointerStore;

final class InMemoryEventStreamPointerStore implements EventStreamPointerStore
{
    private array $pointers = [];

    public function get(string $pointerName): EventStreamPointer
    {
        return $this->pointers[$pointerName] ??= EventStreamPointer::beginning();
    }

    public function update(string $pointerName, EventStreamPointer $eventStreamPointer): void
    {
    }
}
