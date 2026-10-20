<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exceptions\InvoicingPlatformException;
use App\Exceptions\SupplierInvoiceSyncAlreadyRunningException;
use App\Message\SyncSupplierInvoicesMessage;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use App\Service\InvoicingPlatform\SupplierInvoiceSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Synchro demandée depuis la PWA : quelle que soit l'issue, l'utilisateur en est prévenu par une notification push.
 */
#[AsMessageHandler]
final readonly class SyncSupplierInvoicesHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private InvoicingPlatformAccountOwner $accountOwner,
        private SupplierInvoiceSyncService $syncService,
        private SupplierInvoiceImportNotifier $notifier,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SyncSupplierInvoicesMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if (null === $user || !$this->accountOwner->isOwner($user)) {
            $this->logger->warning('Synchro des factures fournisseurs refusée : utilisateur non relié à la plateforme.', ['userId' => $message->userId]);

            return;
        }

        try {
            $result = $this->syncService->sync($user);
        } catch (SupplierInvoiceSyncAlreadyRunningException) {
            // La synchro en cours importe déjà les mêmes factures : rien à refaire.
            $this->notifier->notifySyncNotRun($user, SupplierInvoiceImportNotifier::ALREADY_RUNNING_BODY);

            return;
        } catch (InvoicingPlatformException $exception) {
            // Pas de nouvel essai du message : la synchro planifiée reprendra au même point dans l'heure.
            $this->logger->error('Synchro des factures fournisseurs à la demande interrompue.', ['userId' => $message->userId, 'exception' => $exception]);
            $this->notifier->notifySyncNotRun($user, SupplierInvoiceImportNotifier::PLATFORM_UNAVAILABLE_BODY);

            return;
        }

        $this->notifier->notifyRequestedSync($user, $result);

        if (!$result->isSuccessful()) {
            $this->logger->error('Synchro des factures fournisseurs incomplète.', [
                'userId' => $message->userId,
                'errors' => $result->errors,
                'failures' => $result->failures,
            ]);
        }
    }
}
