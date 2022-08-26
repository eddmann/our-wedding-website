<?php declare(strict_types=1);

use App\Framework\Kernel;

if ($_ENV['PROXY_AUTH_KEY_HEADER'] ?? false) {
    $proxyAuthKeyHeader = 'HTTP_' . \str_replace('-', '_', \mb_strtoupper($_ENV['PROXY_AUTH_KEY_HEADER']));
    if ($_ENV['PROXY_AUTH_KEY_VALUE'] !== ($_SERVER[$proxyAuthKeyHeader] ?? '')) {
        \http_response_code(401);
        exit();
    }
}

if ($_ENV['HTTPS'] ?? false) {
    $_SERVER['HTTPS'] = $_ENV['HTTPS'];
}

if ($_ENV['HOST'] ?? false) {
    $_SERVER['HTTP_HOST'] = $_ENV['HOST'];
}

require_once \dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
