<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\User;
use App\Enum\DishSizesEnum;
use App\Form\DishType;
use App\Trait\DishTrait;
use App\Utility\Paths;
use App\Utility\Utility;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class CreateProduct extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use DishTrait;

    protected function instantiateForm(): FormInterface
    {
        $this->product = new Dish();
        $this->product->setSizes(array_merge(...array_map(fn($size) => [$size->value => 0.0], DishSizesEnum::cases())));
        return $this->createForm(DishType::class, $this->product);
    }
}
