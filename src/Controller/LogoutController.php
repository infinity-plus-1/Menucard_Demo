<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LogoutController extends AbstractController
{
    #[Route('/ajax-logout', name: 'ajax_logout', methods: ['POST'])]
    public function logout (
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
        CsrfTokenManagerInterface $tokenManager
    ): Response {
        $tokenStorage->setToken(NULL);
        $session->invalidate();
        dump("IN HERE");
        return new JsonResponse (
            [
                'message' => 'Logged out successfully',
                'status' => 1,
                'csrf_token' => $tokenManager->getToken('authenticate')->getValue()
            ],
            Response::HTTP_ACCEPTED
        );
    }
}
