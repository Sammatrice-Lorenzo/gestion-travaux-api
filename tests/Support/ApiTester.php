<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;

/**
 * Inherited Methods.
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
final class ApiTester extends Actor
{
    use _generated\ApiTesterActions;

    public function loginAs(string $username, string $password): void
    {
        $this->sendPOST('/api/login', [
            'username' => $username,
            'password' => $password,
        ]);

        $response = $this->grabResponse();
        $data = json_decode($response, true);
        $token = $data['token'] ?? null;
        $this->haveHttpHeader('Authorization', "Bearer {$token}");

        $this->amBearerAuthenticated($token);
    }
}
