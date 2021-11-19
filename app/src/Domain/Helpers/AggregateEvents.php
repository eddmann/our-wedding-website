<?php declare(strict_types=1);

namespace App\Domain\Helpers;

/** @psalm-immutable */
final class AggregateEvents implements \IteratorAggregate
{
    /**
     * @psalm-var list<AggregateEvent>
     * @var AggregateEvent[]
     */
    private array $events;

    public function __construct(array $events)
    {
        $this->events = $events;
    }

    public function add(AggregateEvent $event): self
    {
        return new self([...$this->events, $event]);
    }

    public static function make(): self
    {
        return new self([]);
    }

    public function merge(self $that): self
    {
        return new self([...$this->events, ...$that->events]);
    }

    public function isEmpty(): bool
    {
        return empty($this->events);
    }

    /** @return \Traversable<AggregateEvent> */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->events);
    }

    /**
     * @template TReturn
     * @template TInitial
     * @psalm-param pure-callable(): TReturn $function
     * @psalm-param TInitial|null $initial
     * @psalm-return TReturn|TInitial|null
     */
    public function reduce(callable $function, $initial)
    {
        return \array_reduce($this->events, $function, $initial);
    }
}
