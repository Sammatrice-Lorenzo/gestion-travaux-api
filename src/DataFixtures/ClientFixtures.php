<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Client;

class ClientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
      
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 5; $i++) {
            $client = (new Client());
            $client->setFirstname($faker->firstName);
            $client->setLastname($faker->lastName);
            $client->setPhoneNumber($faker->phoneNumber);
            $client->setStreetAddress($faker->streetAddress);
            $client->setPostalCode($faker->postcode);
            $client->setCity($faker->city);
        
            $manager->persist($client);
        }

        $manager->flush();
       
    }
}
