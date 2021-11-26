<?php declare(strict_types=1);

namespace App\Ui\Cli\Documentation;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateAggregateEventDiagram extends Command
{
    private const SNAPSHOT_FILES = [
        __DIR__ . '/../../../../tests/Application/Command/EventStoreSnapshots/CreateFoodChoiceCommandTest/test_should_create_food_choice.json',
        __DIR__ . '/../../../../tests/Application/Command/EventStoreSnapshots/SubmitInviteCommandTest/test_successfully_submits_pending_invite_with_all_guests_attending.json',
    ];
    public static $defaultName = 'documentation:aggregate-event-diagram';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$events, $transitions] = $this->getEventsAndTransitions(self::SNAPSHOT_FILES);
        $nodes = \implode("\n", $this->getGraphNodes($events));
        $edges = \implode("\n", $this->getGraphEdges($transitions));

        $output->write(
            <<<DIAGRAM
                digraph Events {
                    fontname = "Bitstream Vera Sans"
                    fontsize = 8
                    node [
                        fontname = "Bitstream Vera Sans"
                        fontsize = 8
                        shape = "record"
                    ]
                    edge [
                        arrowtail = "empty"
                    ]
                    {$nodes}
                    {$edges}
                }
                DIAGRAM
        );

        return Command::SUCCESS;
    }

    private function getEventsAndTransitions(array $snapshotFilePaths): array
    {
        $events = [];
        $transitions = [];

        foreach ($snapshotFilePaths as $snapshotFilePath) {
            $snapshotEvents = \json_decode(\file_get_contents($snapshotFilePath), true)['events'];

            foreach ($snapshotEvents as $event) {
                $events[$event['name']] = \array_keys($event['data']);
            }

            $transitions[] = \array_column($snapshotEvents, 'name');
        }

        return [$events, $transitions];
    }

    private function getGraphNodes(array $events): array
    {
        return \array_map(
            static fn ($data, $name) => \sprintf('"%s" [ label = "{%s|%s\l}" ]', $name, $name, \implode('\l', $data)),
            \array_values($events),
            \array_keys($events)
        );
    }

    private function getGraphEdges(array $transitions): array
    {
        $nodeTransitions = [];

        foreach ($transitions as $transition) {
            for ($i = 0; $i < \count($transition) - 1; ++$i) {
                $nodeTransitions[] = '"' . $transition[$i] . '" -> "' . $transition[$i + 1] . '"';
            }
        }

        return \array_unique($nodeTransitions);
    }
}
