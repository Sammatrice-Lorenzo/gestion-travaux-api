<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exceptions\InvoicingPlatformException;
use App\Exceptions\SupplierInvoiceSyncAlreadyRunningException;
use App\Formatter\SupplierInvoiceSyncReportFormatter;
use App\Message\SyncSupplierInvoicesMessage;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use App\Service\InvoicingPlatform\SupplierInvoiceImportNotifier;
use App\Service\InvoicingPlatform\SupplierInvoiceSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * Synchro demandée depuis la PWA : quelle que soit l'issue, l'utilisateur en est prévenu par une notification push.
 * Une synchro interrompue ou incomplète fait échouer le message, sans nouvel essai (la synchro planifiée reprendra
 * au même point dans l'heure) : le tableau de bord Messenger, les logs et GlitchTip la voient comme un échec.
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

    /**
     * @throws UnrecoverableMessageHandlingException si la synchro est interrompue ou incomplète
     *
     * @return array<string, string> bilan conservé dans l'historique des messages traités (tableau de bord Messenger)
     */
    public function __invoke(SyncSupplierInvoicesMessage $message): array
    {
        $user = $this->userRepository->find($message->userId);
        if (null === $user || !$this->accountOwner->isOwner($user)) {
            $this->logger->warning('Synchro des factures fournisseurs refusée : utilisateur non relié à la plateforme.', ['userId' => $message->userId]);

            return ['status' => 'refused'];
        }

        try {
            $result = $this->syncService->sync($user);
        } catch (SupplierInvoiceSyncAlreadyRunningException) {
            // La synchro en cours importe déjà les mêmes factures : rien à refaire.
            $this->notifier->notifySyncNotRun($user, SupplierInvoiceImportNotifier::ALREADY_RUNNING_BODY);

            return ['status' => 'already_running'];
        } catch (InvoicingPlatformException $exception) {
            $this->notifier->notifySyncNotRun($user, SupplierInvoiceImportNotifier::PLATFORM_UNAVAILABLE_BODY);

            throw new UnrecoverableMessageHandlingException("Synchro interrompue : {$exception->getMessage()}", previous: $exception);
        }

        $this->notifier->notifyRequestedSync($user, $result);

        $report = SupplierInvoiceSyncReportFormatter::format($result);
        if (!$result->isSuccessful()) {
            throw new UnrecoverableMessageHandlingException("Synchro incomplète.\n\n{$report}");
        }

        return ['status' => 'completed', 'report' => $report];
    }
}
