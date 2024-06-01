<?php

namespace App\Repository;

use App\Entity\WorkEventDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkEventDay>
 *
 * @method WorkEventDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkEventDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkEventDay[]    findAll()
 * @method WorkEventDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class WorkEventDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkEventDay::class);
    }
}
