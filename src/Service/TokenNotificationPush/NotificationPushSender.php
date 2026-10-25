<?php

declare(strict_types=1);

namespace App\Service\TokenNotificationPush;

use App\Entity\TokenNotificationPush;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Envoie une notification push à une liste d'appareils. Un appareil injoignable est journalisé sans bloquer les autres.
 */
final readonly class NotificationPushSender
{
    public function __construct(
        private FirebaseNotificationServiceInterface $firebaseNotificationService,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param iterable<TokenNotificationPush> $tokenNotificationPushes
     *
     * @return int nombre de notifications acceptées par Firebase
     */
    public function send(iterable $tokenNotificationPushes, string $title, string $body): int
    {
        $sent = 0;
        foreach ($tokenNotificationPushes as $tokenNotificationPush) {
            if ($this->sendTo($tokenNotificationPush, $title, $body)) {
                ++$sent;
            }
        }

        return $sent;
    }

    private function sendTo(TokenNotificationPush $tokenNotificationPush, string $title, string $body): bool
    {
        try {
            $response = $this->firebaseNotificationService->sendNotification($tokenNotificationPush->getToken(), $title, $body);
        } catch (RuntimeException $exception) {
            $this->logger->warning('Notification push impossible.', ['title' => $title, 'exception' => $exception]);

            return false;
        }

        // Firebase renvoie le nom du message créé ; sans lui, l'envoi a été refusé.
        if (array_key_exists('name', $response)) {
            return true;
        }

        $this->logger->warning('Notification push refusée.', ['title' => $title, 'response' => $response]);

        return false;
    }
}
