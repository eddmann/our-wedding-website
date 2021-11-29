<?php declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Invite\InviteAuthenticator;
use App\Domain\Model\Invite\InviteId;
use App\Domain\Model\Invite\InviteType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class SymfonyInviteAuthenticator extends AbstractAuthenticator implements InviteAuthenticator
{
    private const INVITE_SESSION_KEY = 'invite';

    public function __construct(private SessionInterface $session)
    {
    }

    public function login(InviteId $id, InviteType $type): void
    {
        $this->session->set(self::INVITE_SESSION_KEY, [
            'id' => $id->toString(),
            'role' => \mb_strtoupper("ROLE_{$type->toString()}"),
        ]);

        $this->session->migrate();
    }

    public function logout(): void
    {
        $this->session->remove(self::INVITE_SESSION_KEY);
    }

    public function supports(Request $request): ?bool
    {
        return $this->session->has(self::INVITE_SESSION_KEY);
    }

    public function authenticate(Request $request): PassportInterface
    {
        $credentials = $this->session->get(self::INVITE_SESSION_KEY);

        return new SelfValidatingPassport(
            new UserBadge(
                $credentials['id'],
                static fn () => new InMemoryUser($credentials['id'], null, [$credentials['role']])
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
