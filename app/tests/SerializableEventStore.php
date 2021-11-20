<?php declare(strict_types=1);

namespace App\Tests;

use App\Domain\Helpers\EventStore;

interface SerializableEventStore extends EventStore
{
    public function toArray(): array;
}
