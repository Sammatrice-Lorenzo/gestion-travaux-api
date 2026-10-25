<?php

declare(strict_types=1);

namespace App\Tests\Support\Fake;

use App\Interface\Firebase\FirebaseNotificationServiceInterface;

final class FakeFirebaseNotificationService implements FirebaseNotificationServiceInterface
{
    /**
     * @var list<array{token: string, title: string, body: string}> notifications « envoyées », pour les assertions
     */
    public array $sent = [];

    public function sendNotification(string $token, string $title, string $body): array
    {
        $this->sent[] = ['token' => $token, 'title' => $title, 'body' => $body];

        return ['name' => "{$title} {$body}"];
    }
}
