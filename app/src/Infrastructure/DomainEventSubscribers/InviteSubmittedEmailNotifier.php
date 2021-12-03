<?php declare(strict_types=1);

namespace App\Infrastructure\DomainEventSubscribers;

use App\Domain\Event\InviteSubmitted;
use App\Domain\Helpers\DomainEventSubscriber;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class InviteSubmittedEmailNotifier implements DomainEventSubscriber
{
    private MailerInterface $mailer;
    /** @var string[] */
    private array $emailNotifierTo;
    private string $emailNotifierFrom;

    /** @param string[] $emailNotifierTo */
    public function __construct(
        MailerInterface $mailer,
        array $emailNotifierTo,
        string $emailNotifierFrom
    ) {
        $this->mailer = $mailer;
        $this->emailNotifierTo = $emailNotifierTo;
        $this->emailNotifierFrom = $emailNotifierFrom;
    }

    public function __invoke(InviteSubmitted $event): void
    {
        $guests = \array_map(
            static fn (array $guest) => $guest['name'] . (! $guest['attending'] ? ' (Not Attending)' : ''),
            $event->guests
        );

        $email = (new Email())
            ->to(...$this->emailNotifierTo)
            ->from($this->emailNotifierFrom)
            ->subject('An invite has been submitted!')
            ->text(\implode("\n", $guests));

        $this->mailer->send($email);
    }
}
