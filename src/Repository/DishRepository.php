<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Dish;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dish>
 */
class DishRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dish::class);
    }

        /**
         * @return Dish[] Returns an array of Dish objects
        */
        public function findByLikeQuery (
            Company $company,
            string $filter,
            string $field,
            string $orderByField = 'id',
            bool $orderAsc = true,
            int $maxResults = 10,
            int $offset = 0,
            bool $notCaseSensitive = false
        ): array
        {
            $whereQuery =
                "d.$field LIKE "
            .   (
                    $notCaseSensitive
                    ? 'LOWER('
                    : ''
                )
            .   ':filter'
            .   (
                    $notCaseSensitive
                    ? ')'
                    : ''
                )
            .   ' AND d.deleted = false AND d.company = :company'
            ;
            return $this->createQueryBuilder('d')
                ->andWhere($whereQuery)
                ->setParameter('filter', '%' . ($notCaseSensitive ? strtolower($filter) : $filter) . '%')
                ->setParameter('company', $company)
                ->orderBy("d.$orderByField", $orderAsc ? 'ASC' : 'DESC')
                ->setMaxResults($maxResults)
                ->setFirstResult($offset)
                ->getQuery()
                ->getResult()
            ;
        }

    //    public function findOneBySomeField($value): ?Dish
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
