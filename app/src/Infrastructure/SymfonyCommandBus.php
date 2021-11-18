<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\{Command, CommandBus, CommandNotRegistered};
use Symfony\Component\Messenger\Exception\{HandlerFailedException, NoHandlerForMessageException};
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyCommandBus implements CommandBus
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /** @throws CommandNotRegistered */
    public function dispatch(Command $command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (NoHandlerForMessageException $exception) {
            throw new CommandNotRegistered($command);
        } catch (HandlerFailedException $exception) {
            while ($exception instanceof HandlerFailedException) {
                $exception = $exception->getPrevious();
            }

            if (null !== $exception) {
                throw $exception;
            }
        }
    }
}
