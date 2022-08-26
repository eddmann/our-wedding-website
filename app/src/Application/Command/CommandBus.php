<?php declare(strict_types=1);

namespace App\Application\Command;

interface CommandBus
{
    /** @throws CommandNotRegistered */
    public function dispatchSync(Command $command): void;

    /** @throws CommandNotRegistered */
    public function dispatchAsync(Command $command): void;
}
