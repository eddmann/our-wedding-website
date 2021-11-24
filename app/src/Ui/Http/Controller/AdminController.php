<?php declare(strict_types=1);

namespace App\Ui\Http\Controller;

use App\Application\Query\AttendingGuestListingQuery;
use App\Application\Query\FoodChoiceListingQuery;
use App\Application\Query\InviteListingQuery;
use App\Application\Query\SongChoiceListingQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'invites')]
    public function invites(InviteListingQuery $listing): Response
    {
        return $this->render('admin/invites.html.twig', [
            'invites' => $listing->query(),
        ]);
    }

    #[Route('/food-choices', name: 'food-choices')]
    public function foodChoices(FoodChoiceListingQuery $listing): Response
    {
        return $this->render('admin/food-choices.html.twig', [
            'choices' => $listing->query(),
        ]);
    }

    #[Route('/attending-guests', name: 'attending-guests')]
    public function attendingGuests(AttendingGuestListingQuery $listing): Response
    {
        return $this->render('admin/attending-guests.html.twig', [
            'guests' => $listing->query(),
        ]);
    }

    #[Route('/song-choices', name: 'song-choices')]
    public function songChoices(SongChoiceListingQuery $listing): Response
    {
        return $this->render('admin/song-choices.html.twig', [
            'choices' => $listing->query(),
        ]);
    }
}
