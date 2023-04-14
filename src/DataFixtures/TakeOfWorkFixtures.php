<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TakeOfWorkFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $equipements = ["Serveur", "Cables", "Lavabo", "Baie", "Router"];

        foreach ($manager->getRepository(Work::class)->findAll() as $work) {
            for ($i=0; $i < 5; $i++) {
                $progression = $faker->randomElements(Progression::cases())[0]->value;

                $TypeOfWork = (new TypeOfWork())
                    ->setName($faker->name)
                    ->setEquipement($faker->randomElements($equipements))
                    ->setWork($work)
                ;

                $manager->persist($work);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            WorkFixtures::class
        ];
    }
}


