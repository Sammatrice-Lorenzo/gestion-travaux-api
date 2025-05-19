<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Client;
use App\DataFixtures\UserFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;

final class ClientFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach ($manager->getRepository(User::class)->findAll() as $user) {
            for ($i = 0; $i < 5; ++$i) {

                $firstanme = $faker->firstName;
                $lastname = $faker->firstName;
                $client = (new Client())
                    ->setFirstname($firstanme)
                    ->setLastname($lastname)
                    ->setEmail("{$firstanme}-{$lastname}@test.com")
                    ->setPhoneNumber($faker->phoneNumber)
                    ->setStreetAddress($faker->streetAddress)
                    ->setPostalCode($faker->postcode)
                    ->setCity($faker->city)
                    ->setUser($user)
                ;

                $manager->persist($client);
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
