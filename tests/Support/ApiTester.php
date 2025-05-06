<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;
use GuzzleHttp\Client;
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
            'username' => $username ?? 'user@test.com',
            'password' => '1234',
        ]);

        $response = $this->grabResponse();
        $data = json_decode($response, true);
        $token = $data['token'] ?? null;
        $this->haveHttpHeader('Authorization', "Bearer {$token}");

        $this->amBearerAuthenticated($token);
        $this->amOnPage('/api');
    }

    public function loginWithGuzzleHttp(Client $client, ?string $username = null): string
    {
        $response = $client->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'username' => $username ?? 'user@test.com',
                'password' => '1234',
            ],
            'verify' => false,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['token'];
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
