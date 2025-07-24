<?php

namespace App\Controller;

use App\Dto\EditUserDto;
use App\Dto\PasswordDto;
use App\Entity\User;
use App\Form\UpdatePasswordFormType;
use App\Utility\Utility;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
        if (!Utility::isValidUser($user)) {
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

        if (!Utility::isValidUser($user)) {
            $this->addFlash('danger', 'You need to be logged in to view this page.');

            return $this->render('user/password.html.twig', [
                'status' => 403,
            ]);
        }
        $dto = new PasswordDto();
        $form = $this->createForm(UpdatePasswordFormType::class, $dto);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($uphi->isPasswordValid($user, $dto->password)) {
                $form
                    ->get('password')
                    ->get('first')
                    ->addError(new FormError('The new password must differ from your current password.'))
                ;
                $form
                    ->get('password')
                    ->get('second')
                    ->addError(new FormError('The new password must differ from your current password.'))
                ;
            } else {
                $hashedPassword = $uphi->hashPassword($user, $dto->password);
                $user->setPassword($hashedPassword);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Password changed');
                return $this->redirectToRoute('update_password');
            }
        }

        return $this->render('user/password.html.twig', [
            'form' => $form,
            'status' => 200,
            'message' => '',
        ]);
    }

    #[Route('/delete_user', name: 'delete_user')]
    public function delete(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $uphi, Security $security): JsonResponse
    {
        $user = $this->getUser();
        $accepted = $request->request->get('userAccepted');
        $oldPassword = $request->request->get('oldPassword');
        $submittedToken = $request->getPayload()->get('token_delete');

        if (!$this->isCsrfTokenValid('delete-user', $submittedToken) || $accepted !== 'true') {
            return new JsonResponse(['message' => 'Forbidden action.'], 403);
        }

        if (!Utility::isValidUser($user)) {
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

        $user->setForename('deleted');
        $user->setSurname('deleted');
        $user->setStreet('deleted');
        $user->setSn('del');
        $user->setZipcode('deleted');
        $user->setCity('deleted');

        $uuid = '';

        while(($uuid = Uuid::uuid7()) && $em->getRepository(User::class)->findOneBy(['email' => $uuid])) {}

        $user->setEmail($uuid);

        $user->setDeleted(true);

        Utility::deleteCompany($user, $em);

        $user->setCompany(NULL);

        $em->persist($user);

        $em->flush();

        $security->logout(false);
        
        return new JsonResponse('', 200);
    }
}
