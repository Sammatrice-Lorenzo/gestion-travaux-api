<?php

namespace App\Tests\Unit\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\Work;
use App\Entity\Client;
use App\Enum\ProgressionEnum;
use App\Tests\Enum\UserFixturesEnum;

final class WorkTest extends AbstractEntityTestDefault
{
    private DateTime $date;

    private User $user;

    private Client $client;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        /** @var Client $client */
        $client = $this->tester->grabEntity(Client::class);
        $this->date = new DateTime();

        $this->user = $user;
        $this->client = $client;
    }

    public function testRightEntity(): void
    {
        $work = $this->generateValidEntity();

        $this->tester->assertEquals($work->getStart(), $this->date);
        $this->tester->assertEquals($work->getUser(), $this->user);
        $this->tester->assertEquals($work->getClient(), $this->client);
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(3, new Work());
    }

    private function generateValidEntity(): Work
    {
        return (new Work())
            ->setName('Work name test')
            ->setStart($this->date)
            ->setProgression(ProgressionEnum::IN_PROGRESS->value)
            ->setCity('Paris')
            ->setClient($this->client)
            ->setUser($this->user)
        ;
    }
}
