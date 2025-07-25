<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\Dish;
use App\Entity\User;
use App\Repository\DishRepository;
use App\Utility\Utility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DishFilter extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable:true)]
    public string $filter = '';

    public function __construct(private EntityManagerInterface $em){}

    public function getDishes(): array
    {
        $user = $this->getUser();

        if (Utility::isValidUser($user)) {
            $company = $user->getCompany();
            if (Utility::isValidCompany($company)) {
                $companyId = $company->getId();
                if ($companyId) {
                    $repository = $this->em->getRepository(Dish::class);
                    if ($repository instanceof DishRepository) {
                        return array_merge(
                            array_reduce(
                                $repository->findByLikeQuery (
                                    $company,
                                    $this->filter,
                                    'name'
                                ),
                                function ($c, $i) {
                                    if ($i instanceof Dish) {
                                        $c[$i->getName()] = $i;
                                        return $c;
                                    }
                                },
                                []
                            ),
                            array_reduce(
                                $repository->findByLikeQuery (
                                    $company,
                                    $this->filter,
                                    'type'
                                ),
                                function ($c, $i) {
                                    if ($i instanceof Dish) {
                                        $c[$i->getName()] = $i;
                                        return $c;
                                    }
                                },
                                []
                            ),
                            array_reduce(
                                $repository->findByLikeQuery (
                                    $company,
                                    $this->filter,
                                    'category'
                                ),
                                function ($c, $i) {
                                    if ($i instanceof Dish) {
                                        $c[$i->getName()] = $i;
                                        return $c;
                                    }
                                },
                                []
                            ),
                        );
                    }
                    
                }
            }
            return [];
        }
        else return [];
    }

    #[LiveListener('dish_updated')]
    public function updateAfterEdit(): void
    {
        $this->filter = $this->filter;    
    }
}
