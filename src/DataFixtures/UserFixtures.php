<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Tests\Enum\UserFixturesEnum;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->generateUser($manager, UserFixturesEnum::DEFAULT_USER->value);

        for ($i = 0; $i < 5; ++$i) {
            $this->generateUser($manager);
        }

        $manager->flush();
    }

    private function generateUser(ObjectManager $manager, ?string $email = null): void
    {
        $faker = Factory::create('fr_FR');

        $user = (new User())
            ->setFirstname($faker->firstName())
            ->setLastname($faker->lastName())
            ->setEmail($email ?? $faker->email())
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true)
        ;

        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                '1234'
            )
        );
        $manager->persist($user);
    }
}
