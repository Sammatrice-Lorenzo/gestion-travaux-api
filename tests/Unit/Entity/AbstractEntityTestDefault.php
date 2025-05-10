<?php

namespace App\Tests\Unit\Entity;

use Codeception\Test\Unit;
use App\Tests\Support\UnitTester;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractEntityTestDefault extends Unit
{
    protected UnitTester $tester;

    abstract public function testFalseEntity(): void;

    abstract public function testRightEntity(): void;

    protected function assertHasErrors(int $expected, mixed $object): void
    {
        $validator =  $this->tester->grabService(ValidatorInterface::class);

        $this->assertCount($expected, $validator->validate($object));
    }

}
