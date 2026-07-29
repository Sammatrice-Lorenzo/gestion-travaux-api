<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\WorkEventDay;
use App\Helper\DateFormatHelper;
use Doctrine\Persistence\ManagerRegistry;
use App\Interface\MonthlyProviderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<WorkEventDay>
 *
 * @method null|WorkEventDay find($id, $lockMode = null, $lockVersion = null)
 * @method null|WorkEventDay findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method WorkEventDay[]    findAll()
 * @method WorkEventDay[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, ?int $limit = null, ?int $offset = null)
 */
final class WorkEventDayRepository extends ServiceEntityRepository implements MonthlyProviderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkEventDay::class);
    }

    /**
     *
     * @return WorkEventDay[]
     */
    public function findByMonth(User $user, DateTime $month): array
    {
        $firstDayOfMonth = new DateTime("{$month->format('Y-m')}-01");
        $lastDayOfMonth = new DateTime("{$month->format(DateFormatHelper::LAST_DAY_FORMAT)} 23:59:59");

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

    /**
     *
     * @throws \Doctrine\DBAL\Exception when the database rejects the regular expression in $search
     *
     * @return WorkEventDay[]
     */
    public function search(User $user, ?int $clientId, string $search, DateTime $startDate, DateTime $endDate): array
    {
        $startOfDay = (clone $startDate)->setTime(0, 0, 0);
        $endOfDay = (clone $endDate)->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.startDate BETWEEN :startDate AND :endDate')
            ->andWhere('REGEXP(w.title, :search) = 1')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startOfDay)
            ->setParameter('endDate', $endOfDay)
            ->setParameter('search', $search)
            ->orderBy('w.startDate', 'ASC')
        ;

        if (null !== $clientId) {
            $queryBuilder
                ->andWhere('w.client = :clientId')
                ->setParameter('clientId', $clientId)
            ;
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement('SET SESSION MAX_EXECUTION_TIME=2000');

        try {
            return $queryBuilder->getQuery()->getResult();
        } finally {
            $connection->executeStatement('SET SESSION MAX_EXECUTION_TIME=0');
        }
    }
}
