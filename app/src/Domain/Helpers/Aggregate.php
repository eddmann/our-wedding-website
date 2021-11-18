<?php declare(strict_types=1);

namespace App\Domain\Helpers;

abstract class Aggregate
{
    private AggregateEvents $events;
    private AggregateVersion $version;

    protected function __construct()
    {
        $this->events = AggregateEvents::make();
        $this->version = AggregateVersion::zero();
    }

    abstract public function getAggregateName(): AggregateName;

    abstract public function getAggregateId(): AggregateId;

    public function getAggregateVersion(): AggregateVersion
    {
        return $this->version;
    }

    public function flushEvents(): AggregateEvents
    {
        $events = $this->events;

        $this->events = AggregateEvents::make();

        return $events;
    }

    public static function buildFrom(AggregateEvents $events): static
    {
        $aggregate = new static();

        foreach ($events as $event) {
            $aggregate->apply($event);
        }

        return $aggregate;
    }

    protected function raise(AggregateEvent $event): void
    {
        $this->apply($event);

        $this->events = $this->events->add($event);
    }

    protected function apply(AggregateEvent $event): void
    {
        $this->{$this->toEventApplyMethodName($event)}($event);

        $this->version = $this->version->next();
    }

    private function toEventApplyMethodName(AggregateEvent $event): string
    {
        $eventName = \explode('\\', \get_class($event));

        return 'apply' . $eventName[\count($eventName) - 1];
    }
}
