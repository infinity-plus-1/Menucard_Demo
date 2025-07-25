<?php

namespace App\Repository;

use App\Entity\ExtrasGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExtrasGroup>
 */
class ExtrasGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtrasGroup::class);
    }

    public function findByReturnAssociativeByName(array $searchParams): array
    {
        $qb = $this->createQueryBuilder('o', 'o.name');

        $and = $qb->expr()->andX();

        foreach ($searchParams as $field => $value) {
            $and->add(
                $qb->expr()->eq("o.$field", ":$field")
            );
        }

        $qb->andWhere($and);

        foreach ($searchParams as $field => $value) {
            $qb->setParameter("$field", $value);
        }

        return $qb
            ->getQuery()
            ->getResult();
        
    }

    //    /**
    //     * @return ExtrasGroup[] Returns an array of ExtrasGroup objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ExtrasGroup
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
