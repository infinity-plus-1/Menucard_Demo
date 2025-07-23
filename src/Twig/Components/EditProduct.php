<?php

namespace App\Twig\Components;

use App\Entity\Dish;
use App\Form\DishType;
use App\Trait\DishTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent]
final class EditProduct extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use DishTrait;

    protected function instantiateForm(): FormInterface
    {
        $this->product = $this->product ?? new Dish();
        $this->preserveImg = $this->product->getImg();
        return $this->createForm(DishType::class, $this->product);
    }
}
