<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Supplier>
 */
final class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function findByName(string $name): ?Supplier
    {
        $qb = $this->createQueryBuilder('s');

        return $qb->where($qb->expr()->like('s.name', ':name'))
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
