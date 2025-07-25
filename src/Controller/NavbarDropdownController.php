<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NavbarDropdownController extends AbstractController
{
    #[Route('/navbar/dropdown', name: 'app_navbar_dropdown')]
    public function renderDropdown(): Response
    {
        
        return $this->render('navbar_dropdown/index.html.twig', [
            'controller_name' => 'NavbarDropdownController',
        ]);
    }
}
