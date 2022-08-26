<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateFoodChoice\CreateFoodChoiceCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportFoodChoicesCommand extends Command
{
    private CommandBus $bus;

    public function __construct(CommandBus $commandBus)
    {
        parent::__construct('app:import-food-choices');

        $this->addArgument('file', InputArgument::REQUIRED);

        $this->bus = $commandBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');

        $choices = \json_decode_array(\file_get_contents($file));

        $output->writeln(
            \sprintf(
                'Importing <info>%s</info> food choices from <info>%s</info>',
                \count($choices),
                $file
            )
        );

        foreach ($choices as $choice) {
            $this->bus->dispatchSync(
                new CreateFoodChoiceCommand($choice['guestType'], $choice['course'], $choice['name'])
            );
        }

        return Command::SUCCESS;
    }
}
