<?php declare(strict_types=1);

namespace App\Ui\Http\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/healthz', name: 'health')]
final class HealthController
{
    public function __invoke(Connection $connection): Response
    {
        $isUp = (bool) $connection->executeQuery('SELECT 1')->fetchOne();

        if ($isUp) {
            return new Response('Up');
        }

        return new Response('Down', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
