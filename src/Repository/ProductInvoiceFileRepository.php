<?php

namespace App\Repository;

use DateTime;
use App\Entity\ProductInvoiceFile;
use App\Entity\User;
use App\Helper\DateHelper;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ProductInvoiceFile>
 */
final class ProductInvoiceFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductInvoiceFile::class);
    }

    /**
     * @return ProductInvoiceFile[]
     */
    public function findByMonth(User $user, DateTime $date): array
    {
        $period = DateHelper::getDatePeriodForMonth($date);

        return $this->createQueryBuilder('p')
            ->andWhere('p.date BETWEEN :start AND :end')
            ->andWhere('p.user = :user')
            ->setParameter('start', $period->getStartDate())
            ->setParameter('end', $period->getEndDate())
            ->setParameter('user', $user)
            ->orderBy('p.date', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
