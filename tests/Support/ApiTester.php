<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Codeception\Actor;
use App\Tests\Enum\UserFixturesEnum;
use App\Tests\Support\Trait\GrabEntityTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
        $token = $this->extractAuthTokenFromLoginResponse();

        if (null === $token || '' === $token) {
            throw new \RuntimeException('Unable to authenticate: no JWT token found in login response.');
        }

        $this->haveHttpHeader('Authorization', "Bearer {$token}");

        $this->amBearerAuthenticated($token);
        $this->amOnPage('/api');
    }

    private function extractAuthTokenFromLoginResponse(): ?string
    {
        $response = $this->grabResponse();
        $data = json_decode($response, true);

        if (is_array($data) && isset($data['token']) && is_string($data['token'])) {
            return $data['token'];
        }

        try {
            $setCookie = $this->grabHttpHeader('Set-Cookie');
        } catch (\Throwable) {
            return null;
        }

        if (preg_match('/BEARER=([^;]+)/', $setCookie, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
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

    public function assertFileIsUploaded(string $directory, string $fileName): void
    {
        /** @var ParameterBagInterface $parameterBagInterface */
        $parameterBagInterface = $this->grabService(ParameterBagInterface::class);
        $this->assertFileExists($parameterBagInterface->get($directory) . $fileName);
    }

    public function assertFileIsDeleted(string $directory, string $fileName): void
    {
        /** @var ParameterBagInterface $parameterBagInterface */
        $parameterBagInterface = $this->grabService(ParameterBagInterface::class);
        $this->assertFileDoesNotExist($parameterBagInterface->get($directory) . $fileName);
    }
}
