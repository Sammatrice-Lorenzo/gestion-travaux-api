<?php

namespace App\DataFixtures;

use DateTime;
use DateInterval;
use Faker\Factory;
use App\Entity\User;
use App\Entity\WorkEventDay;
use App\Helper\DateFormatHelper;
use App\Tests\Enum\UserFixturesEnum;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class WorkEventDayFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $defaultUser */
        $defaultUser = $manager->getRepository(User::class)->findOneBy([
            'email' => UserFixturesEnum::DEFAULT_USER->value,
        ]);
        /** @var User $user */
        $user = $manager->getRepository(User::class)->find(2);

        $this->generateDefaultWorkEventDay($defaultUser, $manager);
        $this->generateDefaultWorkEventDay($user, $manager);

        $manager->flush();
    }

    private function generateDefaultWorkEventDay(User $user, ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $colors = ['#ff9800', '#940c0c'];
        $defaultFormat = DateFormatHelper::DEFAULT_FORMAT;

        $startDate = new DateTime();
        $endDate = (clone $startDate)->add(new DateInterval('P5D'));

        for ($iDate = $startDate; $iDate < $endDate; $iDate->add(new DateInterval('P1D'))) {
            $start = new DateTime("{$iDate->format($defaultFormat)} 08:00:00");
            $end = new DateTime("{$iDate->format($defaultFormat)} 18:00:00");

            $workEventDay = (new WorkEventDay())
                ->setTitle("Événement {$faker->title}")
                ->setStartDate($start)
                ->setEndDate($end)
                ->setColor($faker->randomElement($colors))
                ->setUser($user)
            ;

            $manager->persist($workEventDay);
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
