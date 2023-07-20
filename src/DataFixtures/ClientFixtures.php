<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Client;
use App\DataFixtures\UserFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ClientFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach ($manager->getRepository(User::class)->findAll() as $user) {
            for ($i = 0; $i < 5; $i++) {
                $client = (new Client());
                $client->setFirstname($faker->firstName);
                $client->setLastname($faker->lastName);
                $client->setPhoneNumber($faker->phoneNumber);
                $client->setStreetAddress($faker->streetAddress);
                $client->setPostalCode($faker->postcode);
                $client->setCity($faker->city);
                $client->setUser($user);

                $manager->persist($client);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class
        ];
    }
}
