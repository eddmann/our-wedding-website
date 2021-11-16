<?php declare(strict_types=1);

namespace App\Infrastructure;

use Bref\SymfonyBridge\BrefKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BrefKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $container->import($confDir . '/{packages}/*.yaml');
        $container->import($confDir . '/{packages}/' . $this->environment . '/*.yaml');

        if (is_file($confDir . '/services.yaml')) {
            $container->import($confDir . '/services.yaml');
            $container->import($confDir . '/{services}_' . $this->environment . '.yaml');
        } else {
            $container->import($confDir . '/{services}.php');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';

        $routes->import($confDir . '/{routes}/' . $this->environment . '/*.yaml');
        $routes->import($confDir . '/{routes}/*.yaml');

        if (is_file($confDir . '/routes.yaml')) {
            $routes->import('../config/routes.yaml');
        } else {
            $routes->import($confDir . '/{routes}.php');
        }
    }

    protected function getWritableCacheDirectories(): array
    {
        return [];
    }
}
