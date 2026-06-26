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

    /**
     * @param array<string, mixed> $parameters
     */
    public function sendJsonPut(string $url, array $parameters): void
    {
        $this->deleteHeader('Content-Type');
        $this->haveHttpHeader('Content-Type', 'application/json');
        $this->sendPUT($url, json_encode($parameters, JSON_THROW_ON_ERROR));
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

    public function assertFileExistsInUploadDirectory(string $directory, string $fileName): void
    {
        $this->assertFileExists($this->resolveUploadFilePath($directory, $fileName));
    }

    public function assertFileNotExistsInUploadDirectory(string $directory, string $fileName): void
    {
        $baseDir = $this->getUploadDirectory($directory);
        $directPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileName);

        if (is_file($directPath)) {
            $this->fail(sprintf('File "%s" still exists at "%s".', $fileName, $directPath));
        }

        try {
            $this->findFileInDirectory($baseDir, basename($fileName));
            $this->fail(sprintf('File "%s" still exists under "%s".', $fileName, $baseDir));
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }
    }

    private function getUploadDirectory(string $directory): string
    {
        /** @var ParameterBagInterface $parameterBagInterface */
        $parameterBagInterface = $this->grabService(ParameterBagInterface::class);

        return rtrim((string) $parameterBagInterface->get($directory), '/\\');
    }

    private function resolveUploadFilePath(string $directory, string $fileName): string
    {
        $baseDir = $this->getUploadDirectory($directory);
        $directPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileName);

        if (is_file($directPath)) {
            return $directPath;
        }

        return $this->findFileInDirectory($baseDir, basename($fileName));
    }

    private function findFileInDirectory(string $baseDir, string $basename): string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $basename) {
                return $file->getPathname();
            }
        }

        throw new \RuntimeException(sprintf('File "%s" not found under "%s".', $basename, $baseDir));
    }
}
