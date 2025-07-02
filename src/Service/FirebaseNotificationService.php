<?php

namespace App\Service;

use Firebase\JWT\JWT;
use RuntimeException;
use App\Dto\FirebaseServiceAccount;
use App\Factory\FirebaseServiceAccountFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class FirebaseNotificationService
{
    private FirebaseServiceAccount $serviceAccount;

    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $parameterBagInterface
    ) {
        $this->serviceAccount = FirebaseServiceAccountFactory::fromJsonFile($parameterBagInterface->get('firebase_admin'));
    }

    /**
     * @throws RuntimeException
     *
     * @return array<string, string>
     */
    public function sendNotification(string $token, string $title, string $body): array
    {
        /** @var string $urlFront */
        $urlFront = $this->parameterBagInterface->get('url_front');

        try {
            $jwt = $this->generateAccessToken();
            $url = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $this->serviceAccount->project_id);

            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => "Bearer {$jwt}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'webpush' => [
                            'notification' => [
                                'icon' => "{$urlFront}/icons/favicon.png",
                                'click_action' => $urlFront,
                            ],
                        ],
                        'android' => [
                            'priority' => 'high',
                            'direct_boot_ok' => true,
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                        ],
                    ],
                ],
            ]);

            return $response->toArray(false);
        } catch (\Throwable $e) {
            throw new RuntimeException('Firebase notification sending failed: ' . $e->getMessage());
        }
    }

    private function generateAccessToken(): string
    {
        $now = time();
        $expires = $now + 3600;

        $payload = [
            'iss' => $this->serviceAccount->client_email,
            'sub' => $this->serviceAccount->client_email,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $expires,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $jwt = JWT::encode($payload, $this->serviceAccount->private_key, 'RS256');
        $response = $this->client->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
        ]);

        $data = $response->toArray();

        return $data['access_token'];
    }
}
