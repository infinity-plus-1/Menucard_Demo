<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    #[Route('/validatelogin', name: 'validateLogin')]
    public function index(): Response
    {
        $attempt = 'partial';
        if (isset($_POST['email']) && isset($_POST['password'])) {
            try {
                $attempt = $this -> validateLogin($_POST['email'], $_POST['password']) ?
                    'successful' : 'failed';
            } catch (\Throwable $th) {
                $attempt = 'Unknown error';
            }
            
        }
        return $this->render('login/index.html.twig', [
            'login_attempt' => $attempt,
        ]);
    }

    private function validateLogin($email, $password) {
        return $email === 'ds@foodyfood.xyz' && $password === 'Password123!';
    }
}
