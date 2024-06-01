<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use Faker\Factory;
use App\Entity\User;
use App\Entity\WorkEventDay;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class WorkEventDayFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'user@test.com']);
        $startDate = new DateTime();
        $endDate = (clone $startDate)->add(new DateInterval('P5D'));

        $startTime = "08:00";
        $endTime = "18:00";

        $colors = ['#ff9800', '#940c0c'];

        for ($iDate = $startDate; $iDate < $endDate; $iDate->add(new DateInterval('P1D'))) {
            $start = new DateTime("{$iDate->format('Y-m-d')} $startTime");
            $end = new DateTime("{$iDate->format('Y-m-d')} $endTime");

            $workEventDay = (new WorkEventDay())
                ->setTitle("Événement {$faker->title}")
                ->setStartDate($start)
                ->setEndDate($end)
                ->setColor($faker->randomElement($colors))
                ->setUser($user)
            ;

            $manager->persist($workEventDay);
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
            ClientFixtures::class
        ];
    }
}
