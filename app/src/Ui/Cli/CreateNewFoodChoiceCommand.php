<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\CommandBus;
use App\Application\Command\CreateFoodChoice\CreateFoodChoiceCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

final class CreateNewFoodChoiceCommand extends Command
{
    public function __construct(private CommandBus $commandBus)
    {
        parent::__construct('app:create-food-choice');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatchSync(
            new CreateFoodChoiceCommand(
                $this->askForGuestType($input, $output),
                $this->askForCourse($input, $output),
                $name = $this->askForName($input, $output),
            )
        );

        $output->writeln("Successfully created new food choice <info>{$name}</info>");

        return Command::SUCCESS;
    }

    private function askForName(InputInterface $input, OutputInterface $output): string
    {
        return $this
            ->getHelper('question')
            ->ask($input, $output, new Question('Please enter the name: '));
    }

    private function askForCourse(InputInterface $input, OutputInterface $output): string
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Please select the course: ',
                    ['starter', 'main', 'dessert']
                )
            );
    }

    private function askForGuestType(InputInterface $input, OutputInterface $output): string
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    'Please select the guest type: ',
                    ['adult', 'child', 'baby']
                )
            );
    }
}
