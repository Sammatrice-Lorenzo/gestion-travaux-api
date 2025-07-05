<?php

namespace App\Tests\Unit\Entity;

use Faker\Factory;
use App\Entity\User;
use Faker\Generator;
use App\Tests\Enum\UserFixturesEnum;
use App\Entity\TokenNotificationPush;

final class TokenNotificationPushTest extends AbstractEntityTestDefault
{
    private User $user;

    private Generator $faker;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
        $this->faker = Factory::create('fr_FR');
    }

    public function testRightEntity(): void
    {
        $tokenNotificationPush = $this->generateValidEntity();

        $this->tester->assertEquals($tokenNotificationPush->getUser(), $this->user);
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(3, new TokenNotificationPush());
    }

    private function generateValidEntity(): TokenNotificationPush
    {
        return (new TokenNotificationPush())
            ->setToken($this->faker->linuxPlatformToken())
            ->setUserAgent($this->faker->userAgent())
            ->setUser($this->user)
        ;
    }
}
