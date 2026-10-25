<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\ApiTester;

final class SupplierInvoiceSyncCest
{
    private const string URL = '/api/qonto/supplier_invoices/sync';

    public function testOwnerStartsSync(ApiTester $I): void
    {
        $I->loginAs();

        $I->sendPost(self::URL);

        $I->seeResponseCodeIs(202);
        $I->seeResponseContainsJson(['message' => 'Synchronisation lancée.']);
    }

    public function testOtherUserIsForbidden(ApiTester $I): void
    {
        /** @var User[] $users */
        $users = $I->grabEntitiesFromRepository(User::class);
        $otherUsers = array_values(array_filter($users, static fn (User $user): bool => UserFixturesEnum::DEFAULT_USER->value !== $user->getEmail()));
        $I->assertNotEmpty($otherUsers, 'Les fixtures doivent contenir un second utilisateur.');

        $I->loginAs((string) $otherUsers[0]->getEmail());
        $I->sendPost(self::URL);

        $I->seeResponseCodeIs(403);
    }

    public function testAnonymousIsUnauthorized(ApiTester $I): void
    {
        $I->sendPost(self::URL);

        $I->seeResponseCodeIs(401);
    }
}
