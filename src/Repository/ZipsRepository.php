<?php

namespace App\Repository;

use App\Entity\Zips;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zips>
 */
class ZipsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zips::class);
    }

    //    /**
    //     * @return Zips[] Returns an array of Zips objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('z')
    //            ->andWhere('z.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('z.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Zips
    //    {
    //        return $this->createQueryBuilder('z')
    //            ->andWhere('z.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
