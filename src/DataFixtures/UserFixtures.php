<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $simpleUser = (new User())
            ->setEmail('user@test.com')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true)
        ;
        $simpleUser->setPassword($this->userPasswordHasher->hashPassword($simpleUser, '1234'));

        for ($i = 0; $i < 5; $i++) {
            $user = (new User())
                ->setEmail($faker->email())
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

        $manager->persist($simpleUser);
        $manager->flush();
    }
}
