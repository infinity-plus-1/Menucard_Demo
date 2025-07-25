<?php

namespace App\Twig\Components;

use App\Entity\Extra;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ExtraFinder
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'updatedExtraName')]
    public string $extraName = '';

    #[LiveProp]
    public array $extras = [];

    public function __construct(private EntityManagerInterface $em) {}

    public function updatedExtraName(): void
    {
        if ($this->extraName === '') {
            $this->extras = [];
            return;
        }

        $this->extras = $this->em->getRepository(Extra::class)->getExtrasByName($this->extraName);
    }

    
}
