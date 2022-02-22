<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointerStore;
use App\Domain\Helpers\Projector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class HandleProjectorCommand extends Command implements SignalableCommandInterface
{
    private bool $isActive = true;
    private iterable $projectors;

    public function __construct(
        private EventStore $eventStore,
        private EventStreamPointerStore $eventStreamPointerStore,
        #[TaggedIterator('app.projector')] iterable $projectors
    ) {
        parent::__construct('app:projector:handle');

        $this->addArgument('projectorNames', InputArgument::IS_ARRAY, '', []);

        $this->projectors = $projectors;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectorNames = (array) $input->getArgument('projectorNames');

        $output->writeln(
            \sprintf(
                'Managing <info>%s</info> projection%s',
                empty($projectorNames) ? 'all' : \implode(', ', $projectorNames),
                \count($projectorNames) === 1 ? '' : 's',
            )
        );

        /** @var Projector[] $projectors */
        $projectors = \array_filter(
            [...$this->projectors],
            static fn (Projector $projector) => empty($projectorNames) || \in_array($projector->getName(), $projectorNames, true)
        );

        while ($this->isActive) {
            foreach ($projectors as $projector) {
                $projector->handle($this->eventStore, $this->eventStreamPointerStore);
            }

            \sleep(1);
        }

        return Command::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $this->isActive = false;
    }
}
