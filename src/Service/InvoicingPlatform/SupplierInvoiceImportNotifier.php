<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\User;
use App\Repository\TokenNotificationPushRepository;
use App\Service\TokenNotificationPush\NotificationPushSender;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Prévient l'utilisateur (notification push sur ses appareils) du résultat d'une synchro des factures fournisseurs.
 * N'échoue jamais : la synchro a déjà eu lieu, une notification perdue est seulement journalisée.
 */
final readonly class SupplierInvoiceImportNotifier
{
    public const string TITLE = 'Factures fournisseurs';

    public const string ALREADY_RUNNING_BODY = 'Une synchronisation Qonto est déjà en cours : ses factures arrivent dans quelques instants';

    public const string PLATFORM_UNAVAILABLE_BODY = 'Qonto est indisponible : la synchronisation sera retentée automatiquement';

    public const string NOTHING_NEW_BODY = 'Aucune nouvelle facture fournisseur sur Qonto';

    public function __construct(
        private NotificationPushSender $notificationPushSender,
        private TokenNotificationPushRepository $tokenNotificationPushRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Synchro planifiée : prévient seulement quand de nouvelles factures sont arrivées (pas de notification chaque heure).
     *
     * @return int nombre de notifications envoyées
     */
    public function notifyImported(User $user, SupplierInvoiceSyncResult $result): int
    {
        return 0 === $result->created ? 0 : $this->send($user, self::importedBody($result->created));
    }

    /**
     * Synchro demandée depuis la PWA : l'utilisateur est toujours prévenu du résultat, même sans nouvelle facture.
     *
     * @return int nombre de notifications envoyées
     */
    public function notifyRequestedSync(User $user, SupplierInvoiceSyncResult $result): int
    {
        return $this->send($user, self::resultBody($result));
    }

    /**
     * Synchro demandée qui n'a pas pu s'exécuter (déjà en cours, plateforme indisponible).
     *
     * @return int nombre de notifications envoyées
     */
    public function notifySyncNotRun(User $user, string $body): int
    {
        return $this->send($user, $body);
    }

    public static function importedBody(int $created): string
    {
        return 1 === $created
            ? '1 nouvelle facture fournisseur importée depuis Qonto'
            : "{$created} nouvelles factures fournisseurs importées depuis Qonto";
    }

    public static function resultBody(SupplierInvoiceSyncResult $result): string
    {
        if (!$result->isSuccessful()) {
            $inError = count($result->errors) + count($result->failures);

            return "Synchronisation Qonto incomplète : {$result->created} importée(s), {$inError} en erreur";
        }

        return 0 === $result->created ? self::NOTHING_NEW_BODY : self::importedBody($result->created);
    }

    private function send(User $user, string $body): int
    {
        try {
            // Par identifiant : l'utilisateur peut être détaché si la synchro a dû réinitialiser l'EntityManager.
            $tokenNotificationPushes = $this->tokenNotificationPushRepository->findBy(['user' => $user->getId()]);

            return $this->notificationPushSender->send($tokenNotificationPushes, self::TITLE, $body);
        } catch (Throwable $exception) {
            $this->logger->warning('Notification de synchro des factures fournisseurs impossible.', ['userId' => $user->getId(), 'exception' => $exception]);

            return 0;
        }
    }
}
