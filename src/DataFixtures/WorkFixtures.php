<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use Faker\Factory;
use App\Entity\Work;
use App\Enum\Progression;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class WorkFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i=0; $i < 10; $i++) {
            $progression = $faker->randomElements(Progression::cases())[0]->value;
            $work = (new Work())
                ->setCity($faker->city)
                ->setStart(new Datetime())
                ->setEnd((new DateTime())->add(new DateInterval('P30D')))
                ->setEquipement(["Serveur", "Cables"])
                ->setProgression($progression)
            ;

            $manager->persist($work);
        }
    
        $manager->flush();
    }
}
