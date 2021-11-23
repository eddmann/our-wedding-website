<?php declare(strict_types=1);

namespace App\Ui\Cli\Documentation;

use App\Application\Command\CommandHandler;
use App\Domain\Helpers\DomainEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommandDiagram extends Command
{
    protected static $defaultName = 'documentation:command-diagram';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commands = \array_map(
            /** @psalm-param class-string $handlerClassName */
            function (string $handlerClassName) {
                $handlerClass = new \ReflectionClass($handlerClassName);

                return $this->getCommandDetails($handlerClass) + [
                    'handler' => $handlerClassName,
                    'domainEvents' => $this->getDomainEvents($handlerClass),
                ];
            },
            $this->getAllDeclaredCommandHandlers()
        );

        $nodes = \implode("\n", $this->getGraphNodes($commands));
        $edges = \implode("\n", $this->getGraphEdges($commands));

        $output->write(
            <<<DIAGRAM
                digraph Commands {
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

    /** @psalm-suppress UnresolvableInclude */
    private function getAllDeclaredCommandHandlers(): array
    {
        $srcPath = \realpath(__DIR__ . '/../../../');

        $phpFiles = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcPath)),
            '/\.php$/'
        );

        foreach ($phpFiles as $phpFile) {
            require_once $phpFile;
        }

        $handlers = \array_filter(
            \get_declared_classes(),
            static fn (string $className) => \in_array(CommandHandler::class, \class_implements($className), true)
        );

        \sort($handlers);

        return $handlers;
    }

    /** @psalm-suppress UndefinedMethod, PossiblyNullReference */
    private function getDomainEvents(\ReflectionClass $handlerClass): array
    {
        $matches = [];

        \preg_match_all('/use (.*);\n/', \file_get_contents($handlerClass->getFileName()), $matches);

        $domainEvents = \array_filter(
            $matches[1] ?? [],
            static fn (string $className) => \in_array(DomainEvent::class, \class_implements($className), true)
        );

        return \array_map(function (string $className) {
            /** @psalm-var class-string $className */
            $class = new \ReflectionClass($className);

            return [
                'name' => $className,
                'payload' => \array_map(
                    fn (\ReflectionParameter $parameter) => [
                        'name' => $parameter->getName(),
                        'type' => $this->getShortClassName($parameter->getType()->getName()),
                    ],
                    $class->getConstructor()?->getParameters() ?: []
                ),
            ];
        }, $domainEvents);
    }

    /** @psalm-suppress UndefinedMethod, PossiblyNullReference */
    private function getCommandDetails(\ReflectionClass $handlerClass): array
    {
        $command = (new \ReflectionMethod($handlerClass->getName(), '__invoke'))
            ->getParameters()[0]
            ->getClass();

        return [
            'name' => $command->getName(),
            'payload' => \array_map(
                fn (\ReflectionParameter $parameter) => [
                    'name' => $parameter->getName(),
                    'type' => $this->getShortClassName($parameter->getType()->getName()),
                ],
                $command->getConstructor()?->getParameters() ?: []
            ),
        ];
    }

    private function getGraphNodes(array $commands): array
    {
        return \array_map(function (array $command) {
            $commandNode = \sprintf(
                '"%s" [ label = "{%s|%s\l}" ]',
                $shortCommandClassName = $this->getShortClassName($command['name']),
                $shortCommandClassName,
                \implode('\l', \array_map(static fn (array $attribute) => \implode(': ', $attribute), $command['payload']))
            );

            $handlerNode = \sprintf(
                '"%s" [ label = "{%s}" ]',
                $shortHandlerClassName = $this->getShortClassName($command['handler']),
                $shortHandlerClassName
            );

            $domainEventNodes = \array_map(
                fn (array $domainEvent) => \sprintf(
                    '"%s" [ label = "{%s|%s\l}" ]',
                    $shortDomainEventClassName = $this->getShortClassName($domainEvent['name']),
                    $shortDomainEventClassName,
                    \implode('\l', \array_map(static fn (array $attribute) => \implode(': ', $attribute), $domainEvent['payload']))
                ),
                $command['domainEvents']
            );

            return \implode("\n", [$commandNode, $handlerNode, ...$domainEventNodes]);
        }, $commands);
    }

    private function getGraphEdges(array $commands): array
    {
        return \array_map(function (array $command) {
            $commandToHandler = \sprintf(
                '"%s" -> "%s"',
                $this->getShortClassName($command['name']),
                $shortHandlerClassName = $this->getShortClassName($command['handler']),
            );

            $handlerToDomainEvents = \array_map(
                fn (array $domainEvent) => \sprintf(
                    '"%s" -> "%s"',
                    $shortHandlerClassName,
                    $this->getShortClassName($domainEvent['name'])
                ),
                $command['domainEvents']
            );

            return \implode("\n", [$commandToHandler, ...$handlerToDomainEvents]);
        }, $commands);
    }

    private function getShortClassName(string $fqcnClassName): string
    {
        $separated = \explode('\\', $fqcnClassName);

        return $separated[\count($separated) - 1];
    }
}
