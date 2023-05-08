<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Work;
use App\Entity\TypeOfWork;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TypeOfWorkFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $equipements = ["Serveur", "Cables", "Lavabo", "Baie", "Router"];

        foreach ($manager->getRepository(Work::class)->findAll() as $work) {
            for ($i=0; $i < 5; $i++) {

                $typeOfWork = (new TypeOfWork())
                    ->setName($faker->name)
                    ->setEquipement($faker->randomElements($equipements))
                    ->setWork($work)
                ;

                $manager->persist($typeOfWork);
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
