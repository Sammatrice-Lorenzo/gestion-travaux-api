<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Supplier;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class SupplierFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach ($manager->getRepository(User::class)->findAll() as $user) {
            for ($i = 0; $i < 5; ++$i) {

                $supplier = (new Supplier())
                    ->setName($faker->name)
                    ->setPhone($faker->phoneNumber)
                    ->setAddress($faker->streetAddress)
                    ->setCity($faker->city)
                    ->setUser($user)
                    ->setCountry($faker->country)
                    ->setVatNumber('FR12345678901')
                ;

                $manager->persist($supplier);
            }
        }

        $manager->flush();
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
