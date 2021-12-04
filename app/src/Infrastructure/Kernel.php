<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\CommandHandler;
use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Helpers\DomainEventSubscriber;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    protected function getWritableCacheDirectories(): array
    {
        return [];
    }

    protected function build(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(CommandHandler::class)
            ->addTag('messenger.message_handler', ['bus' => 'command.bus']);

        $container
            ->registerForAutoconfiguration(AggregateEventsSubscriber::class)
            ->addTag('messenger.message_handler', ['bus' => 'aggregate_event.bus']);

        $container
            ->registerForAutoconfiguration(DomainEventSubscriber::class)
            ->addTag('messenger.message_handler', ['bus' => 'domain_event.bus']);
    }
}
