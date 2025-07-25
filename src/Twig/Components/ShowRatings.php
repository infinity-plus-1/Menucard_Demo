<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\Order;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ShowRatings
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    const MAX_PER_PAGE = 3;

    #[LiveProp]
    public int $page = 1;

    public ?Pagerfanta $pager = NULL;

    #[LiveProp]
    public ?Company $company = NULL;

    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getOrders(): iterable {
        $qb = $this->em->getRepository(Order::class)->createQueryBuilder('o');

        $qb
            ->andWhere(
                $qb->expr()->andX()
                    ->add($qb->expr()->eq('o.company', ':company'))
                    ->add($qb->expr()->isNotNull('o.rating'))
            )
            ->setParameter(
                'company', $this->company
            )
        ;
        $this->pager = new Pagerfanta(
            new QueryAdapter($qb)
        );

        $this->pager->setMaxPerPage(self::MAX_PER_PAGE);
        $this->pager->setCurrentPage($this->page);

        return $this->pager->getCurrentPageResults();
    }

    public function hasNextPage(): bool
    {
        if (!$this->pager) {
            $this->getOrders();
        }
        return $this->pager->hasNextPage();
    }

    #[LiveAction]
    public function loadMore(): void
    {
        if ($this->hasNextPage()) {
            $this->page++;
        }
    }
}
