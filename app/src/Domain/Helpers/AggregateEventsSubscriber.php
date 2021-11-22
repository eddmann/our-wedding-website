<?php declare(strict_types=1);

namespace App\Domain\Helpers;

abstract class AggregateEventsSubscriber
{
    public function __invoke(AggregateEvents $events): void
    {
        foreach ($events as $event) {
            $this->handle($event);
        }
    }

    private function handle(AggregateEvent $event): void
    {
        $method = $this->toEventHandleMethodName($event);

        if (\method_exists($this, $method)) {
            $this->{$method}($event);
        }
    }

    private function toEventHandleMethodName(AggregateEvent $event): string
    {
        $eventName = \explode('\\', \get_class($event));

        return 'handle' . $eventName[\count($eventName) - 1];
    }
}
