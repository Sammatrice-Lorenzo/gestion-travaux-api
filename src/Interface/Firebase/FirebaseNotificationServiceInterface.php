<?php

namespace App\Interface\Firebase;

use RuntimeException;

interface FirebaseNotificationServiceInterface
{
    /**
     * @throws RuntimeException
     *
     * @return array<string, array<string, int|string>|string>
     */
    public function sendNotification(string $token, string $title, string $body): array;
}
