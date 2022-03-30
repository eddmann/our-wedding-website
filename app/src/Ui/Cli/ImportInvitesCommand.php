<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportInvitesCommand extends Command
{
    private CommandBus $bus;

    public function __construct(CommandBus $commandBus)
    {
        parent::__construct('app:import-invites');

        $this->addArgument('file', InputArgument::REQUIRED);

        $this->bus = $commandBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');

        $invites = \json_decode_array(\file_get_contents($file));

        $output->writeln(
            \sprintf(
                'Importing <info>%s</info> invites from <info>%s</info>',
                \count($invites),
                $file
            )
        );

        foreach ($invites as $invite) {
            $this->bus->dispatch(new CreateInviteCommand($invite['type'], $invite['guests']));
        }

        return Command::SUCCESS;
    }
}
