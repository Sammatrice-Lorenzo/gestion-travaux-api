<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Faker\Factory;
use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use App\Tests\Enum\UserFixturesEnum;
use App\Entity\TokenNotificationPush;

final class TokenNotificationPushCest
{
    private User $user;

    public function _before(ApiTester $I): void
    {
        $I->loginAs();

        /** @var User $user */
        $user = $I->grabEntityFromRepository(User::class, ['email' => UserFixturesEnum::DEFAULT_USER->value]);
        $this->user = $user;
    }

    public function testSaveTokenNotificatoinPush(ApiTester $I): void
    {
        $faker = Factory::create('fr_FR');
        $parameters = [
            'token' => $faker->linuxPlatformToken(),
            'userAgent' => $faker->userAgent(),
        ];

        $I->sendPost('/api/token_notification_pushes', $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'userAgent' => $parameters['userAgent'],
        ]);
    }

    #[Depends('testSaveTokenNotificatoinPush')]
    public function testUpdateTokenNotificatoinPush(ApiTester $I): void
    {
        $faker = Factory::create('fr_FR');
        $parameters = [
            'token' => $faker->linuxPlatformToken(),
            'userAgent' => $faker->userAgent(),
        ];

        /** @var TokenNotificationPush $tokenNotificationPush */
        $tokenNotificationPush = $I->grabEntity(TokenNotificationPush::class, ['user' => $this->user]);

        $I->assertNotEquals($parameters['token'], $tokenNotificationPush->getToken());

        $I->sendPost('/api/token_notification_pushes', $parameters);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseMatchesJsonType([]);
    }
}
