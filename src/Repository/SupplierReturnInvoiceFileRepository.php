<?php

declare(strict_types=1);

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Helper\DateHelper;
use App\Entity\SupplierReturnInvoiceFile;
use Doctrine\Persistence\ManagerRegistry;
use App\Interface\MonthlyProviderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<SupplierReturnInvoiceFile>
 */
final class SupplierReturnInvoiceFileRepository extends ServiceEntityRepository implements MonthlyProviderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierReturnInvoiceFile::class);
    }

    /**
     * @return SupplierReturnInvoiceFile[]
     */
    public function findByMonth(User $user, DateTime $date): array
    {
        $period = DateHelper::getDatePeriodForMonth($date);

        return $this->createQueryBuilder('s')
            ->andWhere('s.date BETWEEN :start AND :end')
            ->andWhere('s.user = :user')
            ->setParameter('start', $period->getStartDate())
            ->setParameter('end', $period->getEndDate())
            ->setParameter('user', $user)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
