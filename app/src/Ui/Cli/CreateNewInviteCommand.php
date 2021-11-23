<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateInvite\CreateInviteCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\{ChoiceQuestion, ConfirmationQuestion, Question};

final class CreateNewInviteCommand extends Command
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        parent::__construct('app:create-invite');

        $this->commandBus = $commandBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $this->askForInviteType($input, $output);

        $invitedGuests = [];
        while ($invitedGuest = $this->askForInvitedGuest($input, $output)) {
            $invitedGuests[] = $invitedGuest;
        }

        if (empty($invitedGuests)) {
            $output->writeln('<error>Invites must contain at least one guest</error>');

            return Command::FAILURE;
        }

        $this->commandBus->dispatch($command = new CreateInviteCommand($type, $invitedGuests));

        $output->writeln(\sprintf('Successfully created new invite <info>%s</info>', $command->getCode()->toString()));

        return Command::SUCCESS;
    }

    private function askForInviteType(InputInterface $input, OutputInterface $output): string
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Please select the invite type: ',
                    ['day', 'evening']
                )
            );
    }

    private function askForInvitedGuest(InputInterface $input, OutputInterface $output): ?array
    {
        $hasNextGuest = $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    'Add another guest? <info>[y/N]</info> ',
                    false
                )
            );

        if (! $hasNextGuest) {
            return null;
        }

        $type = $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Please select the guest type: ',
                    ['adult', 'child', 'baby']
                )
            );

        $name = $this
            ->getHelper('question')
            ->ask($input, $output, new Question('Please enter the name: '));

        return \compact('type', 'name');
    }
}
