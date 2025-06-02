<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Trait\GrabEntityTrait;

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
    use GrabEntityTrait;

    public function loginAs(?string $username = null): void
    {
        $this->sendPOST('/api/login', [
            'username' => $username ?? UserFixturesEnum::DEFAULT_USER->value,
            'password' => '1234',
        ]);

        $this->seeResponseCodeIsSuccessful();
        $response = $this->grabResponse();
        $data = json_decode($response, true);
        $token = $data['token'] ?? null;
        $this->haveHttpHeader('Authorization', "Bearer {$token}");

        $this->amBearerAuthenticated($token);
        $this->amOnPage('/api');
    }

    public function createFile(string $fileName, mixed $file): string
    {
        $this->removeFile($fileName);

        file_put_contents($fileName, $file);

        return "./{$fileName}";
    }

    public function removeFile(string $file): void
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
