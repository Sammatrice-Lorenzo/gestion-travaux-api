<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use Faker\Factory;
use App\Entity\User;
use App\Entity\Work;
use App\Entity\Client;
use App\Enum\Progression;
use App\DataFixtures\UserFixtures;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class WorkFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $equipements = ["Serveur", "Cables", "Lavabo", "Baie", "Router"];
        $clients = $manager->getRepository(Client::class)->findAll();

        foreach ($manager->getRepository(User::class)->findAll() as $user) {
            for ($i=0; $i < 5; $i++) {
                $progression = $faker->randomElements(Progression::cases())[0]->value;

                $work = (new Work())
                    ->setName($faker->name)
                    ->setCity($faker->city)
                    ->setStart(new Datetime())
                    ->setEnd((new DateTime())->add(new DateInterval('P30D')))
                    ->setEquipements($faker->randomElements($equipements))
                    ->setProgression($progression)
                    ->setUser($user)
                    ->setClient($faker->randomElement($clients))
                ;

                $manager->persist($work);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            ClientFixtures::class
        ];
    }
}
