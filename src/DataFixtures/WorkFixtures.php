<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use Faker\Factory;
use App\Entity\User;
use App\Entity\Work;
use Faker\Generator;
use App\Entity\Client;
use App\Enum\ProgressionEnum;
use App\DataFixtures\UserFixtures;
use App\DataFixtures\Data\WorkData;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class WorkFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        foreach ($manager->getRepository(User::class)->findAll() as $user) {
            $this->generateWork($faker, $manager, $user);
        }

        $manager->flush();
    }

    private function generateWork(Generator $faker, ObjectManager $manager, User $user): void
    {
        $clients = $manager->getRepository(Client::class)->findBy(['user' => $user]);
        $progression = $faker->randomElements(ProgressionEnum::cases())[0]->value;

        for ($i = 0; $i < 5; ++$i) {
            $work = (new Work())
                ->setName($faker->name)
                ->setCity($faker->city)
                ->setStart(new Datetime())
                ->setEnd((new DateTime())->add(new DateInterval('P30D')))
                ->setEquipements($faker->randomElements(WorkData::EQUIPEMENTS))
                ->setProgression($progression)
                ->setUser($user)
                ->setClient($faker->randomElement($clients))
                ->setInvoice(null)
                ->setTotalAmount(round($faker->randomFloat(), 2))
            ;

            $manager->persist($work);
        }
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ClientFixtures::class,
        ];
    }
}
