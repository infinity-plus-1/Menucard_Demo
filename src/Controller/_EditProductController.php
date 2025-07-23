<?php

namespace App\Controller;

use App\Trait\DishTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class _EditProductController extends AbstractController
{
    // use DishTrait;


    // #[Route('/dishes/edit/{id}', name: 'edit_product')]
    // public function index(EntityManagerInterface $em, Request $request): Response
    // {
    //     $id = $request->attributes->get('id');
    //     return $this->render('edit_product/index.html.twig', [
    //         'dish' => $this->getDish($id, $em),
    //     ]);
    // }
}
