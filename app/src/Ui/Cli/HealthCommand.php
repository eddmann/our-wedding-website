<?php declare(strict_types=1);

namespace App\Ui\Cli;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HealthCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct('app:health');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isConnected = $this->connection->connect();

        $output->writeln($isConnected ? 'Up' : 'Down');

        return $isConnected ? Command::SUCCESS : Command::FAILURE;
    }
}
