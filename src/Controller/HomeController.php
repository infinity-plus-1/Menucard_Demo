<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {

        $session = $request->getSession();
        dump($session);
        $session->set('logged_in', false);
        dump($session->get('logged_in') === true);
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    public function _renderCarousel(): Response {
        return $this->render('home/_carousel.html.twig', [
            
        ]);
    }
}
