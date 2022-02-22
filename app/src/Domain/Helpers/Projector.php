<?php declare(strict_types=1);

namespace App\Domain\Helpers;

abstract class Projector
{
    public function rebuild(EventStore $eventStore, EventStreamPointerStore $eventStreamPointerStore): void
    {
        $eventStreamPointerStore->get($this->getName());

        $this->reset();

        $nextEventStreamPointer = $this->doHandle($eventStore, EventStreamPointer::beginning());

        $eventStreamPointerStore->update($this->getName(), $nextEventStreamPointer);
    }

    public function handle(EventStore $eventStore, EventStreamPointerStore $eventStreamPointerStore): void
    {
        $currentEventStreamPointer = $eventStreamPointerStore->get($this->getName());

        $nextEventStreamPointer = $this->doHandle($eventStore, $currentEventStreamPointer);

        $eventStreamPointerStore->update($this->getName(), $nextEventStreamPointer);
    }

    abstract public function reset(): void;

    abstract public function getName(): string;

    public function getStreamBatchLimit(): int
    {
        return 10;
    }

    private function doHandle(EventStore $eventStore, EventStreamPointer $eventStreamPointer): EventStreamPointer
    {
        while ($stream = $eventStore->stream($eventStreamPointer, $this->getStreamBatchLimit())) {
            $events = $stream->getEvents();

            if ($events->isEmpty()) {
                break;
            }

            foreach ($events as $event) {
                $method = $this->toEventHandleMethodName($event);

                if (\method_exists($this, $method)) {
                    $this->{$method}($event);
                }
            }

            $eventStreamPointer = $stream->getNextPointer();
        }

        return $eventStreamPointer;
    }

    private function toEventHandleMethodName(AggregateEvent $event): string
    {
        $eventName = \explode('\\', \get_class($event));

        return 'handle' . $eventName[\count($eventName) - 1];
    }
}
