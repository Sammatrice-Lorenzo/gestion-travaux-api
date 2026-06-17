<?php

declare(strict_types=1);

namespace App\Tests\Support\Fake;

use App\Interface\Firebase\FirebaseNotificationServiceInterface;

final class FakeFirebaseNotificationService implements FirebaseNotificationServiceInterface
{
    public function sendNotification(string $token, string $title, string $body): array
    {
        return ['name' => "{$title} {$body}"];
    }
}
