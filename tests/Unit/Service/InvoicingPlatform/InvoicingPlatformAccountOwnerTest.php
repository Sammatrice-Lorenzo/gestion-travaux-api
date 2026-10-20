<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\InvoicingPlatform;

use App\Entity\User;
use App\Exceptions\InvoicingPlatformAccountException;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\UnitTester;
use Codeception\Test\Unit;
use LogicException;

final class InvoicingPlatformAccountOwnerTest extends Unit
{
    protected UnitTester $tester;

    private UserRepository $userRepository;

    protected function _before(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->tester->grabService(UserRepository::class);
        $this->userRepository = $userRepository;
    }

    public function testOwnerIsTheUserFoundInDatabase(): void
    {
        $accountOwner = $this->accountOwner(' ' . UserFixturesEnum::DEFAULT_USER->value . ' ');

        $this->assertTrue($accountOwner->isOwner($this->defaultUser()));
        $this->assertFalse($accountOwner->isOwner($this->otherUser()));
    }

    public function testNobodyIsOwnerWhenEmailMatchesNoUser(): void
    {
        $this->assertFalse($this->accountOwner('inconnu@test.com')->isOwner($this->defaultUser()));
    }

    public function testNobodyIsOwnerWhenEmailIsNotConfigured(): void
    {
        $accountOwner = $this->accountOwner('');

        $this->assertFalse($accountOwner->isOwner($this->defaultUser()));
        $this->expectException(InvoicingPlatformAccountException::class);

        $accountOwner->getOwner();
    }

    public function testGetOwner(): void
    {
        $this->assertSame($this->defaultUser(), $this->accountOwner(UserFixturesEnum::DEFAULT_USER->value)->getOwner());
    }

    public function testUnknownEmailThrows(): void
    {
        $this->expectException(InvoicingPlatformAccountException::class);
        $this->expectExceptionMessage('inconnu@test.com');

        $this->accountOwner('inconnu@test.com')->getOwner();
    }

    private function accountOwner(string $email): InvoicingPlatformAccountOwner
    {
        return new InvoicingPlatformAccountOwner($this->userRepository, $email);
    }

    private function defaultUser(): User
    {
        return $this->userRepository->findOneBy(['email' => UserFixturesEnum::DEFAULT_USER->value])
            ?? throw new LogicException('Utilisateur des fixtures introuvable.');
    }

    private function otherUser(): User
    {
        foreach ($this->userRepository->findAll() as $user) {
            if (UserFixturesEnum::DEFAULT_USER->value !== $user->getEmail()) {
                return $user;
            }
        }

        throw new LogicException('Les fixtures doivent contenir un second utilisateur.');
    }
}
