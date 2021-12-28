<?php declare(strict_types=1);

namespace App\Ui\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class DebugShellCommand extends Command
{
    public function __construct()
    {
        parent::__construct('debug:shell');

        $this->addArgument('cmd');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = Process::fromShellCommandline($input->getArgument('cmd'));
        $process->run();

        $output->write($process->isSuccessful() ? $process->getOutput() : $process->getErrorOutput());

        return $process->getExitCode() ?? Command::FAILURE;
    }
}
