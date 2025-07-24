<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatusEnum;
use App\Utility\Utility;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    const BASE_MAX = 1;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    // public function findByCompanyAndDone(Company $company, int &$total, int &$defaultMaxRes): array
    // {
    //     $qb = $this->createQueryBuilder('o')
    //         ->andWhere('o.company = :company');
    //     $qb ->andWhere(
    //             $qb->expr()->orX()
    //                 ->add($qb->expr()->eq('o.status', ':status'))
    //                 ->add($qb->expr()->eq('o.status', ':cancelled'))
    //         )
    //         ->setParameter('company', $company)
    //         ->setParameter('status', OrderStatusEnum::DONE)
    //         ->setParameter('cancelled', OrderStatusEnum::CANCELLED)
    //         ->orderBy('o.id', 'DESC')
    //         ->setMaxResults(self::BASE_MAX);
    //     $paginator = new Paginator($qb);
    //     $total = count($paginator);
    //     $defaultMaxRes = self::BASE_MAX;
    //     return iterator_to_array($paginator);
    // }

    // public function findByCompanyAndPending(Company $company, int &$total, int &$defaultMaxRes): array
    // {
    //     $qb = $this->createQueryBuilder('o')
    //         ->andWhere('o.company = :company')
    //         ->andWhere('o.status = :status')
    //         ->setParameter('company', $company)
    //         ->setParameter('status', OrderStatusEnum::PENDING)
    //         ->orderBy('o.id', 'DESC')
    //         ->setMaxResults(self::BASE_MAX);
    //     $paginator = new Paginator($qb);
    //     $total = count($paginator);
    //     $defaultMaxRes = self::BASE_MAX;
    //     return iterator_to_array($paginator);
    // }

    // public function findByUserAndDone(User $user, int &$total, int &$defaultMaxRes): array
    // {
    //     $qb = $this->createQueryBuilder('o')
    //         ->andWhere('o.user = :user');
    //     $qb ->andWhere(
    //             $qb->expr()->orX()
    //                 ->add($qb->expr()->eq('o.status', ':status'))
    //                 ->add($qb->expr()->eq('o.status', ':cancelled'))
    //         )
    //         ->setParameter('status', OrderStatusEnum::DONE)
    //         ->setParameter('cancelled', OrderStatusEnum::CANCELLED)
    //         ->setParameter('user', $user)
    //         ->orderBy('o.id', 'DESC')
    //         ->setMaxResults(self::BASE_MAX);
    //     $paginator = new Paginator($qb);
    //     $total = count($paginator);
    //     $defaultMaxRes = self::BASE_MAX;
    //     return iterator_to_array($paginator);
    // }

    // public function findByUserAndPending(User $user, int &$total, int &$defaultMaxRes): array
    // {
    //     $qb = $this->createQueryBuilder('o')
    //         ->andWhere('o.user = :user')
    //         ->andWhere('o.status = :status')
    //         ->setParameter('status', OrderStatusEnum::PENDING)
    //         ->setParameter('user', $user)
    //         ->orderBy('o.id', 'DESC')
    //         ->setMaxResults(self::BASE_MAX);
    //     $paginator = new Paginator($qb);
    //     $total = count($paginator);
    //     $defaultMaxRes = self::BASE_MAX;
    //     return iterator_to_array($paginator);
    // }

    public function findByCompanyAndDone(Company $company): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.company = :company');
        return $qb ->andWhere(
                $qb->expr()->orX()
                    ->add($qb->expr()->eq('o.status', ':status'))
                    ->add($qb->expr()->eq('o.status', ':cancelled'))
            )
            ->setParameter('company', $company)
            ->setParameter('status', OrderStatusEnum::DONE)
            ->setParameter('cancelled', OrderStatusEnum::CANCELLED)
            ->orderBy('o.id', 'DESC');
    }

    public function findByCompanyAndPending(Company $company): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.company = :company')
            ->andWhere('o.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', OrderStatusEnum::PENDING)
            ->orderBy('o.id', 'DESC');
    }

    public function findByUserAndDone(User $user): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.user = :user');
        return $qb ->andWhere(
                $qb->expr()->orX()
                    ->add($qb->expr()->eq('o.status', ':status'))
                    ->add($qb->expr()->eq('o.status', ':cancelled'))
            )
            ->setParameter('status', OrderStatusEnum::DONE)
            ->setParameter('cancelled', OrderStatusEnum::CANCELLED)
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC');
    }

    public function findByUserAndPending(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.status = :status')
            ->setParameter('status', OrderStatusEnum::PENDING)
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC');
    }

    public function filterOrders(string $searchTerm, ?User $user, ?Company $company, bool $pending): ?QueryBuilder
    {
        if (!$user && !$company) {
            return NULL;
        }

        $qb = $this->createQueryBuilder('o')
            ->addSelect('u', 'c')
            ->join('o.user', 'u')
            ->join('o.company', 'c');

        $or = $qb->expr()->orX()
            ->add($qb->expr()->like('o.id', ':searchTerm'));

        if ($company) {
            $or ->add($qb->expr()->like('u.forename', ':searchTerm'))
                ->add($qb->expr()->like('u.surname', ':searchTerm'));
        } else {
            $or ->add($qb->expr()->like('c.name', ':searchTerm'));
        }
        $qb->   andWhere($or)
                ->andWhere(
                    $pending
                    ? $qb->expr()->eq('o.status', ':status')
                    : $qb->expr()->orX()
                        ->add($qb->expr()->eq('o.status', ':status'))
                        ->add($qb->expr()->eq('o.status', ':cancelled'))
                )
                ->setParameter('searchTerm', "%$searchTerm%")
                ->setParameter(
                    'status',
                    $pending
                    ? OrderStatusEnum::PENDING
                    : OrderStatusEnum::DONE
                );
        
        if (!$pending) {
            $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
        }

        if ($company) {
            $qb->andWhere('o.company = :company')
                ->setParameter('company', $company);
        } else {
            $qb->andWhere('o.user = :user')
                ->setParameter('user', $user);
        }
        return $qb ->orderBy('o.id', 'DESC');
    }

    // public function filterOrders(string $searchTerm, ?User $user, ?Company $company, bool $pending, int $maxRes, int $offset, int &$total): array
    // {
    //     if (!$user && !$company) {
    //         return [];
    //     }

    //     $qb = $this->createQueryBuilder('o')
    //         ->addSelect('u', 'c')
    //         ->join('o.user', 'u')
    //         ->join('o.company', 'c');

    //     $or = $qb->expr()->orX()
    //         ->add($qb->expr()->like('o.id', ':searchTerm'));

    //     if ($company) {
    //         $or ->add($qb->expr()->like('u.forename', ':searchTerm'))
    //             ->add($qb->expr()->like('u.surname', ':searchTerm'));
    //     } else {
    //         $or ->add($qb->expr()->like('c.name', ':searchTerm'));
    //     }
    //     $qb->   andWhere($or)
    //             ->andWhere(
    //                 $pending
    //                 ? $qb->expr()->eq('o.status', ':status')
    //                 : $qb->expr()->orX()
    //                     ->add($qb->expr()->eq('o.status', ':status'))
    //                     ->add($qb->expr()->eq('o.status', ':cancelled'))
    //             )
    //             ->setParameter('searchTerm', "%$searchTerm%")
    //             ->setParameter(
    //                 'status',
    //                 $pending
    //                 ? OrderStatusEnum::PENDING
    //                 : OrderStatusEnum::DONE
    //             );
        
    //     if (!$pending) {
    //         $qb->setParameter('cancelled', OrderStatusEnum::CANCELLED);
    //     }

    //     if ($company) {
    //         $qb->andWhere('o.company = :company')
    //             ->setParameter('company', $company);
    //     } else {
    //         $qb->andWhere('o.user = :user')
    //             ->setParameter('user', $user);
    //     }
    //     $qb ->orderBy('o.id', 'DESC')
    //         ->setMaxResults($maxRes)
    //         ->setFirstResult($offset * $maxRes);

    //     $paginator = new Paginator($qb);
        
    //     $total = count($paginator);
    //     return iterator_to_array($paginator);
    // }

    public function getOrdersByMonth(string $month, ?Company $company = NULL, ?User $user = NULL): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)');

        $and = $qb->expr()->andX();
        $dateWhere = $qb->expr()->between('o.created', ':from', ':to');
        
        if (Utility::isValidCompany($company)) {
            $and->add($dateWhere)->add($qb->expr()->eq('o.company', ':company'));
            $qb->setParameter('company', $company);
        } elseif (Utility::isValidUser($user)) {
            $and->add($dateWhere)->add($qb->expr()->eq('o.user', ':user'));
            $qb->setParameter('user', $user);
        } else {
            return 0;
        }

        $year = explode(' ', $month)[1];
        $month = explode(' ', $month)[0];

        $firstDay = (new DateTime("first day of $month $year"))->setTime(0, 0, 0);
        $lastDay = (new DateTime("last day of $month $year"))->setTime(23, 59, 59);

        return $qb->andWhere($and)
            ->setParameter('from', $firstDay)
            ->setParameter('to', $lastDay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCompanyRating(Company $company): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.rating)');

        $totalRating = (int) $qb->andWhere(
            $qb->expr()->eq('o.company', ':company')
        )
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)');

        $totalOrders = (int) $qb->andWhere(
            $qb->expr()->andX()
                ->add($qb->expr()->eq('o.company', ':company'))
                ->add($qb->expr()->isNotNull('o.rating'))
        )
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalRating > 0 && $totalOrders > 0) {
            return ['totalRatings' => $totalOrders, 'avgRating' => (float) $totalRating / $totalOrders];
        }

        return ['totalRatings' => 0, 'avgRating' => 0.0];
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
