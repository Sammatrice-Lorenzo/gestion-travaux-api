<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exceptions\UnregisteredPushTokenException;
use App\Service\FirebaseNotificationService;
use Codeception\Test\Unit;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FirebaseNotificationServiceTest extends Unit
{
    private string $serviceAccountFile;

    protected function _before(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (false === $key || !openssl_pkey_export($key, $privateKey)) {
            $this->markTestSkipped('OpenSSL ne peut pas générer de clé ici (OPENSSL_CONF manquant sous Windows).');
        }

        $this->serviceAccountFile = (string) tempnam(sys_get_temp_dir(), 'firebase-account-');
        file_put_contents($this->serviceAccountFile, json_encode([
            'type' => 'service_account',
            'project_id' => 'test',
            'private_key_id' => 'id',
            'private_key' => $privateKey,
            'client_email' => 'test@test.iam.gserviceaccount.com',
            'client_id' => '1',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/test',
            'universe_domain' => 'googleapis.com',
        ], JSON_THROW_ON_ERROR));
    }

    protected function _after(): void
    {
        if (isset($this->serviceAccountFile) && is_file($this->serviceAccountFile)) {
            unlink($this->serviceAccountFile);
        }
    }

    public function testAcceptedMessageIsReturned(): void
    {
        $response = $this->service(['name' => 'projects/test/messages/1'], 200)->sendNotification('token', 'Titre', 'Message');

        $this->assertSame(['name' => 'projects/test/messages/1'], $response);
    }

    public function testUnregisteredTokenThrowsDedicatedException(): void
    {
        $this->expectException(UnregisteredPushTokenException::class);

        $this->service(self::firebaseError(404, 'UNREGISTERED'), 404)->sendNotification('token', 'Titre', 'Message');
    }

    public function testOtherFirebaseErrorIsReturnedToTheCaller(): void
    {
        $error = self::firebaseError(400, 'INVALID_ARGUMENT');

        $this->assertSame($error, $this->service($error, 400)->sendNotification('token', 'Titre', 'Message'));
    }

    public function testNetworkFailureThrowsRuntimeException(): void
    {
        $service = new FirebaseNotificationService(
            new MockHttpClient(new MockResponse('', ['error' => 'Connexion refusée'])),
            new ParameterBag(['firebase_admin' => $this->serviceAccountFile, 'url_front' => 'https://front.test']),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Firebase notification sending failed');

        $service->sendNotification('token', 'Titre', 'Message');
    }

    /**
     * @param array<string, mixed> $firebaseResponse
     */
    private function service(array $firebaseResponse, int $status): FirebaseNotificationService
    {
        return new FirebaseNotificationService(
            new MockHttpClient([
                new MockResponse((string) json_encode(['access_token' => 'jwt'])),
                new MockResponse((string) json_encode($firebaseResponse), ['http_code' => $status]),
            ]),
            new ParameterBag(['firebase_admin' => $this->serviceAccountFile, 'url_front' => 'https://front.test']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function firebaseError(int $code, string $errorCode): array
    {
        return [
            'error' => [
                'code' => $code,
                'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => $errorCode]],
            ],
        ];
    }
}
