<?php

namespace App\Repository;

use App\Entity\ZipMmTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ZipMmTable>
 */
class ZipMmTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZipMmTable::class);
    }

    //    /**
    //     * @return ZipMmTable[] Returns an array of ZipMmTable objects
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

    //    public function findOneBySomeField($value): ?ZipMmTable
    //    {
    //        return $this->createQueryBuilder('z')
    //            ->andWhere('z.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
