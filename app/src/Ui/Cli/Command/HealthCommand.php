<?php declare(strict_types=1);

namespace App\Ui\Cli\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class HealthCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct('app:health');

        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isConnected = $this->connection->connect();

        $output->writeln($isConnected ? 'Up' : 'Down');

        return $isConnected ? 0 : 1;
    }
}
