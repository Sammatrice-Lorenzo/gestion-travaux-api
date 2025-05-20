<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Client;

final class ClientTest extends AbstractEntityTestDefault
{
    private const string CITY = 'Paris';

    private User $user;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => 'user@test.com']);
        $this->user = $user;
    }

    public function testRightEntity(): void
    {
        $client = $this->generateValidEntity();

        $this->tester->assertEquals($client->getUser(), $this->user);
        $this->tester->assertEquals($client->getCity(), self::CITY);
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(6, new Client());
    }

    private function generateValidEntity(): Client
    {
        return (new Client())
            ->setFirstname('Client first name test')
            ->setLastname('Client last name test')
            ->setEmail('client@test.com')
            ->setCity(self::CITY)
            ->setPostalCode('75001')
            ->setStreetAddress('80 rue du Test')
            ->setPhoneNumber('0123456789')
            ->setUser($this->user)
        ;
    }
}
