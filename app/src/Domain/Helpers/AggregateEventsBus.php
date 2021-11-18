<?php declare(strict_types=1);

namespace App\Domain\Helpers;

interface AggregateEventsBus
{
    public function publish(AggregateEvents $events): void;
}
