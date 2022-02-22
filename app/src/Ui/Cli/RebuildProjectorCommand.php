<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Domain\Helpers\EventStore;
use App\Domain\Helpers\EventStreamPointerStore;
use App\Domain\Helpers\Projector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class RebuildProjectorCommand extends Command
{
    private iterable $projectors;

    public function __construct(
        private EventStore $eventStore,
        private EventStreamPointerStore $eventStreamPointerStore,
        #[TaggedIterator('app.projector')] iterable $projectors
    ) {
        parent::__construct('app:projector:rebuild');

        $this->addArgument('projectorNames', InputArgument::IS_ARRAY, '', []);

        $this->projectors = $projectors;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectorNames = (array) $input->getArgument('projectorNames');

        $output->writeln(
            \sprintf(
                'Rebuilding <info>%s</info> projection%s',
                empty($projectorNames) ? 'all' : \implode(', ', $projectorNames),
                \count($projectorNames) === 1 ? '' : 's',
            )
        );

        /** @var Projector $projector */
        foreach ($this->projectors as $projector) {
            if (empty($projectorNames) || \in_array($projector->getName(), $projectorNames, true)) {
                $output->write(\sprintf('- %s... ', $projector->getName()));
                $projector->rebuild($this->eventStore, $this->eventStreamPointerStore);
                $output->writeln('Complete');
            }
        }

        return Command::SUCCESS;
    }
}
