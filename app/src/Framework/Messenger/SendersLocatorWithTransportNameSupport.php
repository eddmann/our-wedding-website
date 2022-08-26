<?php declare(strict_types=1);

namespace App\Framework\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

/**
 * This implements the `TransportNamesStamp` which is being developed for Symfony Messenger.
 * As this is only available Symfony 6.2 at this time, it has been back-ported for use in this current code-base.
 * Being able to explicitly define the desired transport at call-time is required for the Command Bus `dispatchSync`
 * and `dispatchAsync` to work as intended.
 *
 * @see https://github.com/symfony/symfony/pull/39306
 */
final class SendersLocatorWithTransportNameSupport implements SendersLocatorInterface
{
    private ContainerInterface $sendersLocator;

    public function __construct(private SendersLocator $decorated)
    {
        // This is a big NONO for usual code, as we are having to access a private value found in the `SendersLocator`.
        // Sadly due to the way in which the sender's locator is compiled we are unable to access this directly
        // from the container. As this is a temporary solution to a feature that will be released in Symfony going
        // forward this feels acceptable at this time.
        $this->sendersLocator = (fn () => $this->sendersLocator)->call($this->decorated);
    }

    public function getSenders(Envelope $envelope): iterable
    {
        /** @var TransportNamesStamp|null $transportStamps */
        $transportStamps = $envelope->last(TransportNamesStamp::class);

        if (null === $transportStamps) {
            return $this->decorated->getSenders($envelope);
        }

        foreach ($transportStamps->getTransportNames() as $senderAlias) {
            yield $senderAlias => $this->sendersLocator->get($senderAlias);
        }
    }
}
