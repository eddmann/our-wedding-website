<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\AggregateEvents;
use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointerStore;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'test')]
final class SyncProjectorHandler extends AggregateEventsSubscriber
{
    private iterable $projectors;

    public function __construct(
        private EventStore $eventStore,
        private EventStreamPointerStore $eventStreamPointerStore,
        #[TaggedIterator('app.projector')] iterable $projectors
    ) {
        $this->projectors = $projectors;
    }

    public function __invoke(AggregateEvents $events): void
    {
        foreach ($this->projectors as $projector) {
            $projector->handle($this->eventStore, $this->eventStreamPointerStore);
        }
    }
}
