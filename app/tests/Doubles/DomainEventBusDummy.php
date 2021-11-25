<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\DomainEvent;
use App\Domain\Helpers\DomainEventBus;

final class DomainEventBusDummy implements DomainEventBus
{
    public function publish(DomainEvent $event): void
    {
    }
}
