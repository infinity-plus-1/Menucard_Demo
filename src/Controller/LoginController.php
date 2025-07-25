<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/ajax-login', name: 'app_login')]
    public function login(): JsonResponse
    {
        return new JsonResponse(['message' => 'Welcome to the void'], 400);
    }
}
