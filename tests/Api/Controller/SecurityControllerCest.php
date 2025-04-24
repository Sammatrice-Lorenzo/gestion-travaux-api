<?php

declare(strict_types=1);

namespace App\Tests\Api\Controller;

use App\Tests\Support\ApiTester;

final class SecurityControllerCest
{
    public function testLogin(ApiTester $I): void
    {
        $I->amOnPage('/');
        $I->sendPost('/api/login', [
            'username' => 'user@test.com',
            'password' => '1234',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson('token');
    }
}
