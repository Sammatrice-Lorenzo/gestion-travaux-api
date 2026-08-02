<?php

declare(strict_types=1);

namespace App\Tests\Api\Controller;

use App\Tests\Support\ApiTester;
use App\Tests\Enum\UserFixturesEnum;

final class SecurityControllerCest
{
    public function testLogin(ApiTester $I): void
    {
        $I->amOnPage('/');
        $I->sendPost('/api/login', [
            'username' => UserFixturesEnum::DEFAULT_USER->value,
            'password' => '1234',
        ]);
        $I->seeResponseCodeIsSuccessful();

        $response = $I->grabResponse();
        $data = json_decode($response, true);

        if (is_array($data) && isset($data['token']) && is_string($data['token'])) {
            $I->seeResponseContainsJson(['token' => $data['token']]);

            return;
        }

        $I->seeHttpHeader('Set-Cookie');
        $I->assertStringContainsString('BEARER=', $I->grabHttpHeader('Set-Cookie'));
    }
}
