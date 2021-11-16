<?php declare(strict_types=1);

namespace App\Ui\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/', name: 'home')]
final class HomeController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('hello.html.twig');
    }
}
