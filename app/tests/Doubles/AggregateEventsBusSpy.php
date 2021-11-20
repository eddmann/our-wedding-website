<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\{AggregateEvents, AggregateEventsBus};

final class AggregateEventsBusSpy implements AggregateEventsBus
{
    private ?AggregateEvents $events;

    public function getLastEvents(): ?AggregateEvents
    {
        return $this->events;
    }

    public function publish(AggregateEvents $events): void
    {
        $this->events = $events;
    }
}
