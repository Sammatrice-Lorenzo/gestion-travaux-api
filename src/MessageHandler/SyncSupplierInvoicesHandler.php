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

    /**
     * @throws InvoicingPlatformException plateforme indisponible : Messenger retente le message, puis le passe en « failed »
     */
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
            $this->logger->info('Synchro des factures fournisseurs déjà en cours, demande ignorée.', ['userId' => $message->userId]);

            return;
        }

        $this->notifier->notify($user, $result);

        if (!$result->isSuccessful()) {
            $this->logger->error('Synchro des factures fournisseurs incomplète.', [
                'userId' => $message->userId,
                'errors' => $result->errors,
                'failures' => $result->failures,
            ]);
        }
    }
}
