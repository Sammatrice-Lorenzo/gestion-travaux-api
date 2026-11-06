<?php

declare(strict_types=1);

namespace App\Interface\Firebase;

use App\Exceptions\UnregisteredPushTokenException;
use RuntimeException;

interface FirebaseNotificationServiceInterface
{
    /**
     * @throws UnregisteredPushTokenException si le token n'est plus enregistré chez Firebase
     * @throws RuntimeException
     *
     * @return array<string, array<string, int|string>|string>
     */
    public function sendNotification(string $token, string $title, string $body): array;
}
