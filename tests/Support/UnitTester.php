<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
*/
final class UnitTester extends Actor
{
    use _generated\UnitTesterActions;


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
        // return $this->grabService('doctrine.orm.entity_manager');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->grabService(EntityManagerInterface::class);

        return $entityManager;
    }

}
