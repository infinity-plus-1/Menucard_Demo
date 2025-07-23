<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\User;
use App\Trait\DishTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\LiveComponent\DefaultActionTrait;

class _ListProductsController extends AbstractController
{
    use DefaultActionTrait;
    use DishTrait;

    public string $filter = '';

    // #[Route('/dishes', name: 'list_dishes')]
    // public function listAllDishes(EntityManagerInterface $em, Request $request): Response
    // {
    //     $dishes = [];
    //     $user = $this->getUser();

    //     if ($user instanceof User) {
    //         $company = $user->getCompany();
    //         if ($company instanceof Company) {
    //             $companyId = $company->getId();
    //             if ($companyId) {
    //                 $dishes = $em->getRepository(Dish::class)->findBy(['company' => $companyId]);
    //             }
    //         }
    //     }
    //     return $this->render('dish/list.html.twig', [
    //         'dishes' => $dishes,
    //     ]);
    // }

    // #[Route('/dishes/{id}', name: 'view_dish')]
    // public function viewDish(EntityManagerInterface $em, Request $request): Response
    // {
    //     $id = $request->attributes->get('id');
        
    //     return $this->render('dish/view.html.twig', [
    //         'dish' => $this->getDish($id, $em),
    //     ]);
    // }
}
