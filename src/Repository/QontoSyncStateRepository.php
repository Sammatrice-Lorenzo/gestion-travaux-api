<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QontoSyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QontoSyncState>
 */
final class QontoSyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QontoSyncState::class);
    }
}
