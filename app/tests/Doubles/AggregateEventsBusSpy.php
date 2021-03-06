<?php declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventsBus;

final class AggregateEventsBusSpy implements AggregateEventsBus
{
    private ?AggregateEvents $events = null;

    public function getLastEvents(): ?AggregateEvents
    {
        return $this->events;
    }

    public function publish(AggregateEvents $events): void
    {
        $this->events = $events;
    }
}
