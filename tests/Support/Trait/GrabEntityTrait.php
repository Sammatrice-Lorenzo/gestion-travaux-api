<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait;

use Doctrine\ORM\EntityManagerInterface;

trait GrabEntityTrait
{
    /**
     * @return object
     */
    public function grabEntity(string $name, array $criteria = [], ?array $orderBy = null): mixed
    {
        return $this->grabEntityManagerInterface()->getRepository($name)->findOneBy($criteria, $orderBy);
    }

    /**
     * @return mixed[]
     */
    public function grabEntities(string $name, array $criteria = [], ?array $orderBy = null, ?int $limit = null): mixed
    {
        return $this->grabEntityManagerInterface()->getRepository($name)->findBy($criteria, $orderBy, $limit);
    }

    public function grabEntityManagerInterface(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        return $this->grabService(EntityManagerInterface::class);
    }
}
