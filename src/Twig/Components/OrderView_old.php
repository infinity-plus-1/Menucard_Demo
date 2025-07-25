<?php

namespace App\Twig\Components;

use App\Entity\Company;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
final class OrderView_old
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public string $filter = '';

    #[LiveProp]
    public array $orders = [];

    #[LiveProp]
    public ?bool $isUser = false;

    #[LiveProp]
    public ?bool $pending = true;

    #[LiveProp]
    public int $maxRes = 1;

    #[LiveProp(writable: true)]
    public int $offset = 0;

    #[LiveProp(writable: true)]
    public int $pages = 0;

    #[LiveProp]
    public int $totalElements = 0;

    public ?Pagerfanta $pager = NULL;

    private ?User $_user = NULL;

    private ?Company $_company = NULL;

    public function __construct(Security $security, private EntityManagerInterface $em, private SerializerInterface $serializer)
    {
        $this->_user = $security->getUser();
        $this->_company = $this->_user->getCompany();
    }

    private function _fetchOrders(): array
    {
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, ?string $format, array $context): string {

                if ($object instanceof Company) {
                    return $object->getId();
                }
                return $object->getId();
            },
        ];
        return json_decode(
            $this->serializer->serialize(
                $this->em->getRepository(Order::class)->filterOrders(
                    $this->filter,
                    $this->_user,
                    $this->_company,
                    $this->pending,
                    $this->maxRes,
                    $this->offset,
                    $this->totalElements
                ),
                'json',
                $context
            ),
            true
        );
    }

    #[LiveAction]
    public function increment(): array
    {
        if (($this->offset + 1) < $this->pages) {
            $this->offset++;
            return $this->getOrders();
        } else {
            return $this->orders;
        }
    }

    #[LiveAction]
    public function decrement(): array
    {
        if ($this->offset > 0) {
            $this->offset--;
            return $this->getOrders();
        } else {
            return $this->orders;
        }
    }

    #[LiveAction]
    public function setPage(#[LiveArg] int $newPage): array
    {
        if ($newPage <= $this->pages) {
            $this->offset = $newPage - 1;
            return $this->getOrders();
        } else {
            return $this->orders;
        }
    }

    #[LiveAction]
    public function changeCount(#[LiveArg] int $mRes): array
    {
        $this->maxRes = $mRes;
        $this->pages = ceil($this->totalElements / $this->maxRes);
        $this->offset = 0;
        $this->filter = '';
        return $this->getOrders();
    }

    #[LiveAction]
    public function getOrders(): array
    {
        $this->orders = $this->_fetchOrders();
        $this->pages = ceil($this->totalElements / $this->maxRes);
        return $this->orders;
    }

    #[LiveAction]
    public function filterOrders(): array
    {
        $this->offset = 0;
        return $this->getOrders();
    }
}
