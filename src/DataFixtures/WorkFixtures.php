<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorkFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i=0; $i < ; $i++) { 
            new (Work())
                ->setCity($faker->city)
                ->setStrart(new Datetime())
                ->setEnd((new DateTime())->addDate('P30D'))
                ->setEquipement()
        }
    
        $manager->flush();
    }
}
