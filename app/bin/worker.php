<?php declare(strict_types=1);

require \dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new \App\Infrastructure\Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();

return $kernel->getContainer()->get(\Bref\Symfony\Messenger\Service\Sqs\SqsConsumer::class);
