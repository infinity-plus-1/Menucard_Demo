<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Test\FormInterface;

class RegistrationController extends AbstractController
{
    private function addFieldErrors(FormInterface $form, array $fieldErrors): void
    {
        foreach ($fieldErrors as $field => $messages) {
            $child = $form->get($field);
            foreach ((array) $messages as $message) {
                $child->addError(new FormError($message));
            }
        }
    }

    #[Route('/registration', name: 'register')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->render('registration/index.html.twig', [
                'form' => $form,
                'width' => $request->request->get('width') ?? 0,
            ]);
        }
        else if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em->persist($data);
            $em->flush();

            return $this->render('registration/reg_success.html.twig', []);
        } else {
            
            
            return $this->render('registration/index.html.twig', [
                'form' => $form,
                'width' => $request->request->get('width') ?? 0,
            ], new Response('', 422));
        }
    }
}
