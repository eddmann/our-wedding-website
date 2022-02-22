<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Helpers\EventStreamPointer;
use App\Domain\Helpers\EventStreamPointerStore;
use Doctrine\DBAL\Connection;

final class DBALEventStreamPointerStore implements EventStreamPointerStore
{
    public function __construct(private Connection $connection)
    {
    }

    public function get(string $pointerName): EventStreamPointer
    {
        $result = $this->connection->executeQuery(
            'SELECT event_stream_pointer FROM public.event_stream_pointers WHERE pointer_name = :pointerName',
            \compact('pointerName')
        );

        if ($pointer = $result->fetchOne()) {
            return new EventStreamPointer($pointer);
        }

        return EventStreamPointer::beginning();
    }

    public function update(string $pointerName, EventStreamPointer $eventStreamPointer): void
    {
        $statement = $this->connection->prepare('
            INSERT INTO public.event_stream_pointers (pointer_name, event_stream_pointer)
            VALUES (:pointerName, :eventStreamPointer)
            ON CONFLICT (pointer_name) DO UPDATE SET event_stream_pointer = :eventStreamPointer
        ');

        $statement->executeStatement([
            'pointerName' => $pointerName,
            'eventStreamPointer' => $eventStreamPointer->toString(),
        ]);
    }
}
