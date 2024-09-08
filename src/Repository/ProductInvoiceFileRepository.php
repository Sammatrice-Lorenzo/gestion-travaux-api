<?php

namespace App\Repository;

use App\Entity\ProductInvoiceFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductInvoiceFile>
 */
final class ProductInvoiceFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductInvoiceFile::class);
    }
}
