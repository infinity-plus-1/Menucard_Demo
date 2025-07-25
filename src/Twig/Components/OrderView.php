<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\Order;
use App\Entity\User;
use App\Utility\Utility;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

#[AsLiveComponent]
final class OrderView
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    const MAX_RES = 1;

    public ?int $maxResFirst = NULL;
    public ?int $maxResSecond = NULL;
    public ?int $maxResThird = NULL;

    #[LiveProp(writable: true)]
    public string $filter = '';

    #[LiveProp]
    public array $orders = [];

    #[LiveProp]
    public ?bool $isUser = false;

    #[LiveProp]
    public ?bool $pending = true;

    #[LiveProp]
    public ?int $maxRes = NULL;

    #[LiveProp]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public int $proximity = 2;

    #[LiveProp]
    public int $status = 200;

    #[LiveProp]
    public string $message = '';

    public ?Pagerfanta $pager = NULL;

    private ?User $_user = NULL;

    private ?Company $_company = NULL;

    public function __construct(Security $security, private EntityManagerInterface $em)
    {
        $this->_user = $security->getUser();
        $this->_company = $this->_user->getCompany();

        $this->isUser = !Utility::isCompanyAccount($this->_user);

        $this->message = 'An unknown error occured';

        $this->maxRes = self::MAX_RES;
        $this->maxResFirst = self::MAX_RES;
        $this->maxResSecond = self::MAX_RES * 2;
        $this->maxResThird = self::MAX_RES * 3;
    }

    private function _fetchOrders(): ?Pagerfanta
    {
        $pagerfanta = NULL;

        $qb = $this->em->getRepository(Order::class)->filterOrders(
            $this->filter,
            $this->_user,
            $this->_company,
            $this->pending,
        );

        if ($qb) {
            $pagerfanta = new Pagerfanta(new QueryAdapter($qb));
        }

        return $pagerfanta;
    }

    #[LiveAction]
    public function setPage(#[LiveArg] int $newPage): void
    {
        if (!$this->pager) {
            $this->_setPager();
        }
        
        if ($this->pager && $newPage <= $this->pager->count()) {
            $this->page = $newPage;
            $this->_setPager();
        }
    }

    #[LiveAction]
    private function _setPager(): void
    {
        $this->message = 'An unknown error occured';
        if (!Utility::accountIsSetUp($this->_user)) {
            $this->pager = NULL;
            $this->status = 400;
            $this->message = 'You have a commercial account but no company registered yet.';
            return;
        }
        $pager = $this->_fetchOrders();

        if ($pager) {
            $this->pager = $pager;
            $this->pager->setMaxPerPage($this->maxRes);
            $this->pager->setCurrentPage($this->page);
        } else {
            $this->status = 500;
            $this->message = 'An unknown error occured';
        }
    }

    #[LiveAction]
    public function filterOrders(): void
    {
        $this->page = 1;
        $this->_setPager();
    }

    #[LiveAction]
    public function changeCount(#[LiveArg] int $mRes): void
    {
        $this->maxRes = $mRes;
        $this->filter = '';
        $this->page = 1;
        $this->_setPager();
    }
}
