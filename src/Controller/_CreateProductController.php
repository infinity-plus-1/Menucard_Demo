<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class _CreateProductController extends AbstractController
{
    //#[Route('/create/product', name: 'create_product')]
    public function index(): Response
    {
        return $this->render('create_product/index.html.twig', [
            'controller_name' => 'CreateProductController',
        ]);
    }
}
