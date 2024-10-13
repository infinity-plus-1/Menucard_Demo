<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImagesliderController extends AbstractController
{
    #[Route('/imageslider', name: 'app_imageslider')]
    public function index(): Response
    {
        return $this->render('imageslider/index.html.twig', [
            'controller_name' => 'ImagesliderController',
        ]);
    }
}
