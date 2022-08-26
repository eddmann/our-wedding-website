<?php declare(strict_types=1);

namespace App\Framework\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

final class TransportNamesStamp implements StampInterface
{
    public function __construct(private array $transports)
    {
    }

    public function getTransportNames(): array
    {
        return $this->transports;
    }
}
