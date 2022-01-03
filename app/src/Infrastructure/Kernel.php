<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\CommandHandler;
use App\Domain\Helpers\AggregateEventsSubscriber;
use App\Domain\Helpers\DomainEventSubscriber;
use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    public function handle($request, $type = HttpKernelInterface::MAIN_REQUEST, $catch = true): Response
    {
        if ($_ENV['PROXY_AUTH_KEY_HEADER'] ?? false) {
            if ($_ENV['PROXY_AUTH_KEY_VALUE'] !== $request->headers->get($_ENV['PROXY_AUTH_KEY_HEADER'])) {
                return new Response('', Response::HTTP_UNAUTHORIZED);
            }
        }

        if ($_ENV['HOST'] ?? false) {
            $request->headers->set('Host', $_ENV['HOST']);
        }

        return parent::handle($request, $type, $catch);
    }

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
