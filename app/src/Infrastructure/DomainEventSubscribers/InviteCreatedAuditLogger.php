<?php declare(strict_types=1);

namespace App\Infrastructure\DomainEventSubscribers;

use App\Domain\Event\InviteCreated;
use App\Domain\Helpers\DomainEventSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class InviteCreatedAuditLogger implements DomainEventSubscriber
{
    /** @param string[] $emailNotifierTo */
    public function __construct(
        private LoggerInterface $infoLogger,
        private MailerInterface $mailer,
        private array $emailNotifierTo,
        private string $emailNotifierFrom
    ) {
    }

    public function __invoke(InviteCreated $event): void
    {
        $this->infoLogger->info('Invite was created', ['id' => $event->id]);

        $email = (new Email())
            ->to(...$this->emailNotifierTo)
            ->from($this->emailNotifierFrom)
            ->subject('An invite was created')
            ->text("An invite with id {$event->id} was created");

        $this->mailer->send($email);
    }
}
