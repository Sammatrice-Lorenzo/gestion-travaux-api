<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\WorkEventDay;
use App\Helper\DateFormatHelper;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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

    /**
     * @param User $user
     * @param DateTime $month
     * @return WorkEventDay[]
     */
    public function findByMonth(User $user, DateTime $month): array
    {
        $firstDayOfMonth = new DateTime("{$month->format('Y-m')}-01");
        $lastDayOfMonth = new DateTime("{$month->format(DateFormatHelper::LAST_DAY_FORMAT)}");

        return $this->createQueryBuilder('w')
           ->andWhere('w.startDate BETWEEN :start AND :end')
           ->andWhere('w.user = :user')
           ->setParameter('start', $firstDayOfMonth)
           ->setParameter('end', $lastDayOfMonth)
           ->setParameter('user', $user)
           ->orderBy('w.startDate', 'ASC')
           ->getQuery()
           ->getResult()
        ;
    }
}
