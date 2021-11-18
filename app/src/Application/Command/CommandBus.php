<?php declare(strict_types=1);

namespace App\Application\Command;

interface CommandBus
{
    /** @throws CommandNotRegistered */
    public function dispatch(Command $command): void;
}
