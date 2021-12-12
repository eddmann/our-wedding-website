<?php declare(strict_types=1);

use App\Infrastructure\Kernel;

if (($proxyAuthKeyHeader = \getenv('PROXY_AUTH_KEY_HEADER'))
    && \getenv('PROXY_AUTH_KEY_VALUE') !== ($_SERVER[$proxyAuthKeyHeader] ?? '')) {
    \http_response_code(401);
    exit();
}

require_once \dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
