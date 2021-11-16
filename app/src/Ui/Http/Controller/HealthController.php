<?php declare(strict_types=1);

namespace App\Ui\Http\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/healthz', name: 'health')]
class HealthController
{
    public function __invoke(Connection $connection): Response
    {
        if ($connection->connect()) {
            return new Response('Up');
        }

        return new Response('Down', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
