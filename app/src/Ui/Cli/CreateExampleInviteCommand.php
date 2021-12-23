<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateExampleInviteCommand extends Command
{
    public function __construct(private CommandBus $commandBus)
    {
        parent::__construct('app:create-example-invite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = new CreateInviteCommand(
            'day',
            [
                [
                    'type' => 'adult',
                    'name' => 'Example adult',
                ],
                [
                    'type' => 'child',
                    'name' => 'Example child',
                ],
                [
                    'type' => 'baby',
                    'name' => 'Example baby',
                ],
            ]
        );

        $this->commandBus->dispatch($command);

        $output->writeln(\sprintf('Successfully created example invite <info>%s</info>', $command->getCode()->toString()));

        return Command::SUCCESS;
    }
}
