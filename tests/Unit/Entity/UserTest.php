<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;

final class UserTest extends AbstractEntityTestDefault
{
    private const string EMAIL_TEST = 'user-unit@test.com';

    public function testRightEntity(): void
    {
        $user = $this->generateValidEntity();

        $this->tester->assertEquals($user->getEmail(), self::EMAIL_TEST);
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(3, new User());
    }

    private function generateValidEntity(): User
    {
        return (new User())
            ->setEmail(self::EMAIL_TEST)
            ->setFirstname('User first name test')
            ->setLastname('User last name test')
            ->setPassword('securePassword123')
        ;
    }
}
