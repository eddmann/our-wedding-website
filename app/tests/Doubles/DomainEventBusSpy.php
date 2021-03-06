<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\DomainEvent;
use App\Domain\Helpers\DomainEventBus;

final class DomainEventBusSpy implements DomainEventBus
{
    private ?DomainEvent $event = null;

    public function getLastEvent(): ?DomainEvent
    {
        return $this->event;
    }

    public function publish(DomainEvent $event): void
    {
        $this->event = $event;
    }
}
