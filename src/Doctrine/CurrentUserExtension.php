<?php

namespace App\Doctrine;

use ReflectionClass;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\Operation;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use App\Entity\User;
use App\Entity\WorkImage;

final readonly class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $reflectionClass = new ReflectionClass($resourceClass);
        /** @var ?User $user */
        $user = $this->security->getUser();
        if (!$user || !$reflectionClass->hasMethod('getUser')) {
            return;
        }

        if (WorkImage::class === $resourceClass) {
            $this->filterByWorkImage($user, $queryBuilder);

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
        $queryBuilder->setParameter('current_user', $user->getId());
    }

    private function filterByWorkImage(User $user, QueryBuilder $queryBuilder): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->join("{$rootAlias}.work", 'w')
            ->andWhere('w.user = :current_user')
            ->setParameter('current_user', $user->getId())
        ;
    }
}
