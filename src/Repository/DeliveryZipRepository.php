<?php

namespace App\Repository;

use App\Entity\DeliveryZip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliveryZip>
 */
class DeliveryZipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryZip::class);
    }

    /**
     * Collect and return all companies that deliver to the given zip.
     * 
     * @param string $zip The postal code to search for
     * @param array $optionals An array with predefined field names to filter the results with
     * 
     * The array must contain a key => value pair.
     * 
     * Possible pairs are:
     * 
     * 'rating' => floatValue This will filter for all restaurants with an average rating greater or equal to the given value.
     * 'cuisine' => stringValue This looks for restaurants with the related cuisine, defined in CuisinesEnum.php
     * 
     * @return array The array containing the Company entities.
     */
    public function getCompaniesByDeliveryZip(string $zip, array $optionals = [], bool $returnArray = false): QueryBuilder|array
    {
        $qb = $this->createQueryBuilder('d')
            ->addSelect('c')
            ->join('d.company', 'c');

        if ($optionals !== []) {
            foreach ($optionals as $key => $value) {
                $key = strtolower($key);
                switch ($key) {
                    case 'rating':
                        $qb->andWhere(
                            $qb->expr()->gte('c.averageRating', ':rating')
                        )
                        ->setParameter('rating', floatval($value));
                        break;
                    case 'cuisine':
                        if (strtolower($value) !== 'all') {
                            $qb->andWhere(
                                $qb->expr()->eq('c.type', ':cuisine')
                            )
                            ->setParameter('cuisine', $value);
                        }
                        break;
                    default:
                        # skip
                        break;
                }
            }
        }

        $qb
            ->andWhere(
                $qb->expr()->eq('d.zipCode', ':zip')
            )
            ->setParameter('zip', $zip);
        return $returnArray ? $qb->getQuery()->getResult() : $qb;
    }

    //    /**
    //     * @return DeliveryZip[] Returns an array of DeliveryZip objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DeliveryZip
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
