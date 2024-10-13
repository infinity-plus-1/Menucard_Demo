<?php

namespace App\Controller;

use App\Entity\Dish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dish', name: 'dish_')]
class DishController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list(): Response
    {
        return $this->render('dish/index.html.twig', [
            'controller_name' => 'DishController',
        ]);
    }

    #[Route('/add', name: 'add')]
    public function add(EntityManagerInterface $em, Request $request): Response
    {
        $dish = new Dish();
        $dish -> setName('Pizza');
        $em -> persist($dish);
        $em -> flush();
        return new Response('Dish created');
    }
}
