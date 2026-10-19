<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Entity\User;
use App\Exceptions\InvoicingPlatformAccountException;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class InvoicingPlatformAccountOwnerTest extends Unit
{
    private const string EMAIL = 'artisan@test.com';

    public function testOnlyConfiguredUserIsOwner(): void
    {
        $accountOwner = new InvoicingPlatformAccountOwner($this->entityManagerReturning(null), ' ' . self::EMAIL . ' ');

        $this->assertTrue($accountOwner->isOwner((new User())->setEmail('Artisan@Test.com')));
        $this->assertFalse($accountOwner->isOwner((new User())->setEmail('autre@test.com')));
    }

    public function testNobodyIsOwnerWhenEmailIsNotConfigured(): void
    {
        $accountOwner = new InvoicingPlatformAccountOwner($this->entityManagerReturning(null), '');

        $this->assertFalse($accountOwner->isOwner((new User())->setEmail('')));
        $this->expectException(InvoicingPlatformAccountException::class);

        $accountOwner->getOwner();
    }

    public function testGetOwner(): void
    {
        $user = (new User())->setEmail(self::EMAIL);
        $this->assertSame($user, (new InvoicingPlatformAccountOwner($this->entityManagerReturning($user), self::EMAIL))->getOwner());
    }

    public function testUnknownEmailThrows(): void
    {
        $this->expectException(InvoicingPlatformAccountException::class);
        $this->expectExceptionMessage(self::EMAIL);

        (new InvoicingPlatformAccountOwner($this->entityManagerReturning(null), self::EMAIL))->getOwner();
    }

    private function entityManagerReturning(?User $user): EntityManagerInterface
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);

        return $entityManager;
    }
}
