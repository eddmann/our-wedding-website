<?php declare(strict_types=1);

namespace App\Domain\Helpers;

interface EventStreamPointerStore
{
    public function get(string $pointerName): EventStreamPointer;

    public function update(string $pointerName, EventStreamPointer $eventStreamPointer): void;
}
