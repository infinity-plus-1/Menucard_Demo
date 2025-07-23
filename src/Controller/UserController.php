<?php

namespace App\Controller;

use App\Dto\EditUserDto;
use App\Dto\PasswordDto;
use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

final class UserController extends AbstractController
{
    #[Route('/edit_user', name: 'edit_user')]
    public function editUser(Request $request, ObjectMapperInterface $omi): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->render('user/edit.html.twig', [
                'user' => NULL,
                'status' => 404,
                'message' => 'User not found',
            ]); 
        }
        $user = $omi->map($user, EditUserDto::class);
        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/update_password', name: 'update_password')]
    public function updatePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $uphi): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->render('user/password.html.twig', [
                'user' => NULL,
                'status' => 404,
                'message' => 'User not found',
            ]); 
        }
        $dto = new PasswordDto();
        $form = $this->createForm(UpdatePasswordFormType::class, $dto);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $uphi->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);
            $em->persist($user);
            $em->flush();
        }
        
        return $this->render('user/password.html.twig', [
            'user' => $user,
            'form' => $form
        ]);
    }

    #[Route('/delete_user', name: 'delete_user')]
    public function delete(Request $request, UserPasswordHasherInterface $uphi): JsonResponse
    {
        $user = $this->getUser();
        $accepted = $request->request->get('userAccepted');
        $oldPassword = $request->request->get('oldPassword');
        $submittedToken = $request->getPayload()->get('token_delete');

        if (!$this->isCsrfTokenValid('delete-user', $submittedToken) || $accepted !== 'true') {
            return new JsonResponse(['message' => 'Forbidden action.'], 403);
        }

        if (!$user || !$user instanceof User) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        }

        if (!$oldPassword || !is_string($oldPassword) || $oldPassword === '') {
            return new JsonResponse(['message' => 'You need to enter your current password to delete your account.'], 401);
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        if (!$uphi->isPasswordValid($user, $oldPassword)) {
            return new JsonResponse(['message' => 'The current password you have entered does not match.'], 400);
        }
        
        return new JsonResponse('', 200);
    }
}
