<?php

namespace App\Tests\Unit\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\WorkEventDay;

final class WorkEventDayTest extends AbstractEntityTestDefault
{
    private DateTime $start;

    private DateTime $end;

    private User $user;

    public function _before(): void
    {
        /** @var User $user */
        $user = $this->tester->grabEntity(User::class, ['email' => 'user@test.com']);
        $this->start = (new DateTime())->setTime(8, 0);
        $this->end = (new DateTime())->setTime(18, 0);

        $this->user = $user;
    }

    public function testRightEntity(): void
    {
        $workEventDay = $this->generateValidEntity();

        $this->tester->assertEquals($workEventDay->getStartDate(), $this->start);
        $this->tester->assertEquals($workEventDay->getEndDate(), $this->end);
        $this->tester->assertEquals($workEventDay->getUser(), $this->user);
        $this->tester->assertEquals($workEventDay->getColor(), '#ffff');
    }

    public function testFalseEntity(): void
    {
        $this->assertHasErrors(2, new WorkEventDay());
    }

    private function generateValidEntity(): WorkEventDay
    {
        return (new WorkEventDay())
            ->setTitle('Work event day test')
            ->setStartDate($this->start)
            ->setEndDate($this->end)
            ->setUser($this->user)
            ->setColor('#ffff')
        ;
    }
}
