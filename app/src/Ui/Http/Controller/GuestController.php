<?php declare(strict_types=1);

namespace App\Ui\Http\Controller;

use App\Application\Command\AuthenticateInvite\AuthenticateInviteCommand;
use App\Application\Command\CommandBus;
use App\Application\Command\SubmitInvite\SubmitInviteCommand;
use App\Application\Query\InviteRsvpQuery;
use App\Infrastructure\SymfonyInviteAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GuestController extends AbstractController
{
    #[Route('/', name: 'login')]
    public function login(Request $request, CommandBus $commandBus): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('details');
        }

        $form = $this->createForm(LoginType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $commandBus->dispatch(new AuthenticateInviteCommand($form->get('code')->getData()));

                return $this->redirectToRoute('details');
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('guest/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SymfonyInviteAuthenticator $authenticator): Response
    {
        $authenticator->logout();

        return $this->redirectToRoute('login');
    }

    #[Route('/details', name: 'details')]
    public function details(): Response
    {
        return $this->render('guest/details.html.twig');
    }

    #[Route('/menu', name: 'menu')]
    public function menu(): Response
    {
        return $this->render('guest/menu.html.twig');
    }

    #[Route('/accommodation', name: 'accommodation')]
    public function accommodation(): Response
    {
        return $this->render('guest/accommodation.html.twig');
    }

    #[Route('/rsvp', name: 'rsvp')]
    public function rsvp(Request $request, CommandBus $commandBus, InviteRsvpQuery $rsvp): Response
    {
        /** @psalm-suppress PossiblyNullReference */
        $id = $this->getUser()->getUsername();

        $invite = $rsvp->query($id);

        if ($invite['status'] === 'submitted') {
            return $this->render('guest/rsvp/submitted.html.twig', \compact('invite'));
        }

        $form = $this->createForm(InviteRsvpType::class, null, \compact('invite'));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $commandBus->dispatch($this->toSubmitInviteCommand($id, $form->getData()));

                return $this->redirectToRoute('rsvp');
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('guest/rsvp/pending.html.twig', [
            'form' => $form->createView(),
            'invite' => $invite,
        ]);
    }

    private function toSubmitInviteCommand(string $id, array $form): SubmitInviteCommand
    {
        $guests = \array_filter($form['guests'], static fn (array $guest): bool => $guest['attending']);

        $songs = \array_filter($form['songs'], static fn (array $song): bool => $song['track'] && $song['artist']);

        return new SubmitInviteCommand($id, $guests, $songs);
    }
}
