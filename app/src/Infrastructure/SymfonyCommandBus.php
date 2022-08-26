<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\Command;
use App\Application\Command\CommandBus;
use App\Application\Command\CommandNotRegistered;
use App\Framework\Messenger\TransportNamesStamp;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyCommandBus implements CommandBus
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    /** @throws CommandNotRegistered */
    public function dispatchSync(Command $command): void
    {
        try {
            $this->bus->dispatch($command, [new TransportNamesStamp(['sync'])]);
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

    /** @throws CommandNotRegistered */
    public function dispatchAsync(Command $command): void
    {
        try {
            $this->bus->dispatch($command, [new TransportNamesStamp(['async'])]);
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
