<?php declare(strict_types=1);

namespace App\Ui\Cli;

use App\Application\Command\Command as ApplicationCommand;
use App\Domain\Helpers\{AggregateEvent, DomainEvent};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateListOfMessagesCommand extends Command
{
    private const MESSAGE_TYPES = [
        ApplicationCommand::class,
        AggregateEvent::class,
        DomainEvent::class,
    ];
    protected static $defaultName = 'documentation:message-list';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('All Messages');

        $this->requireAllSrc();
        $declaredClasses = \get_declared_classes();
        \sort($declaredClasses);

        foreach (self::MESSAGE_TYPES as $messageType) {
            $io->section('Message Type: ' . $this->getShortClassName($messageType));

            $table = new Table($output);
            $table->setHeaders(['Name', 'Attributes']);

            foreach ($declaredClasses as $fqcnClassName) {
                if (\in_array($messageType, \class_implements($fqcnClassName), true)) {
                    $table->addRow([
                        $this->getShortClassName($fqcnClassName),
                        $this->getMessageAttributes($fqcnClassName),
                    ]);
                }
            }

            $table->render();
            $io->newLine();
        }

        return Command::SUCCESS;
    }

    private function requireAllSrc(): void
    {
        $srcPath = \realpath(__DIR__ . '/../../');

        $phpFiles = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcPath)),
            '/\.php$/'
        );

        foreach ($phpFiles as $phpFile) {
            require_once $phpFile;
        }
    }

    private function getShortClassName(string $fqcnClassName): string
    {
        $separated = \explode('\\', $fqcnClassName);

        return $separated[\count($separated) - 1];
    }

    private function getMessageAttributes(string $fqcnClassName): string
    {
        $attributes = \array_map(
            static fn (\ReflectionParameter $parameter) => $parameter->getName(),
            (new \ReflectionClass($fqcnClassName))->getConstructor()->getParameters()
        );

        return \implode(', ', $attributes);
    }
}
