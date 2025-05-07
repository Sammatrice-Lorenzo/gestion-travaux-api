<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

trait GrabEntityTrait
{
    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $name
     *
     * @return EntityRepository<TEntity>
     */
    private function getRepository(string $name): EntityRepository
    {
        return $this->grabEntityManagerInterface()->getRepository($name);
    }

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $name
     * @param array<string, mixed> $criteria
     * @param null|array<string, string> $orderBy
     *
     * @return null|TEntity
     */
    public function grabEntity(string $name, array $criteria = [], ?array $orderBy = null): mixed
    {
        return $this->getRepository($name)->findOneBy($criteria, $orderBy);
    }

    /**
     * @template TEntity of object
     *
     * @param class-string<TEntity> $name
     * @param array<string, mixed> $criteria
     * @param null|array<string, string> $orderBy
     * @param null|int $limit
     *
     * @return TEntity[]
     */
    public function grabEntities(string $name, array $criteria = [], ?array $orderBy = null, ?int $limit = null): mixed
    {
        return $this->getRepository($name)->findBy($criteria, $orderBy, $limit);
    }

    public function grabEntityManagerInterface(): EntityManagerInterface
    {
        return $this->grabService(EntityManagerInterface::class);
    }
}
