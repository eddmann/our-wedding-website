<?php declare(strict_types=1);

namespace App\Infrastructure\DomainEventSubscribers;

use App\Domain\Event\InviteCreated;
use App\Domain\Helpers\DomainEventSubscriber;
use Psr\Log\LoggerInterface;

final class InviteCreatedAuditLogger implements DomainEventSubscriber
{
    public function __construct(private LoggerInterface $infoLogger)
    {
    }

    public function __invoke(InviteCreated $event): void
    {
        $this->infoLogger->info('Invite was created', ['id' => $event->id]);
    }
}
