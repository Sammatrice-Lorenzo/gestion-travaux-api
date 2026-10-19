<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\TokenNotificationPush;
use App\Entity\User;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Prévient l'utilisateur (notification push sur ses appareils) quand une synchro a importé de nouvelles factures.
 */
final readonly class SupplierInvoiceImportNotifier
{
    public const string TITLE = 'Factures fournisseurs';

    public function __construct(
        private FirebaseNotificationServiceInterface $firebaseNotificationService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Un appareil injoignable est seulement journalisé : la synchro elle-même a réussi.
     *
     * @return int nombre de notifications envoyées
     */
    public function notify(User $user, SupplierInvoiceSyncResult $result): int
    {
        if (0 === $result->created) {
            return 0;
        }

        $body = self::body($result->created);
        $sent = 0;
        foreach ($this->entityManager->getRepository(TokenNotificationPush::class)->findBy(['user' => $user]) as $tokenNotificationPush) {
            try {
                $response = $this->firebaseNotificationService->sendNotification($tokenNotificationPush->getToken(), self::TITLE, $body);
                if (array_key_exists('name', $response)) {
                    ++$sent;

                    continue;
                }

                $this->logger->warning('Notification de factures importées refusée.', ['response' => $response]);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Notification de factures importées impossible.', ['exception' => $exception]);
            }
        }

        return $sent;
    }

    public static function body(int $created): string
    {
        return 1 === $created
            ? '1 nouvelle facture fournisseur importée depuis Qonto'
            : "{$created} nouvelles factures fournisseurs importées depuis Qonto";
    }
}
