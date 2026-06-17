<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TypeOfWork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeOfWork>
 *
 * @method null|TypeOfWork find($id, $lockMode = null, $lockVersion = null)
 * @method null|TypeOfWork findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method TypeOfWork[]    findAll()
 * @method TypeOfWork[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, ?int $limit = null, ?int $offset = null)
 */
final class TypeOfWorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeOfWork::class);
    }

    public function save(TypeOfWork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TypeOfWork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
