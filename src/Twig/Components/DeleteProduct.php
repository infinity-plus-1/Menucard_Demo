<?php

namespace App\Twig\Components;

use App\Trait\DishTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DeleteProduct extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use DishTrait;

    public function __construct(private LoggerInterface $logger){}

    #[LiveProp]
    public ?int $dishId = NULL;
}
