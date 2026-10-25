<?php

declare(strict_types=1);

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\InvoicingPlatform\SupplierInvoiceSyncStatus;
use App\Entity\User;
use App\Message\SyncSupplierInvoicesMessage;
use App\Service\InvoicingPlatform\InvoicingPlatformAccountOwner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

/**
 * Met la synchro en file pour le worker. Plusieurs demandes (appuis répétés, nouvel essai de la PWA) tant que la
 * précédente attend ou tourne ne produisent qu'un seul message.
 *
 * @implements ProcessorInterface<null, SupplierInvoiceSyncStatus>
 */
final readonly class SupplierInvoiceSyncProcessor implements ProcessorInterface
{
    /**
     * Durée maximale du dédoublonnage si le message n'est jamais traité (worker arrêté).
     */
    private const float DEDUPLICATE_TTL_SECONDS = 600.0;

    public function __construct(
        private MessageBusInterface $messageBus,
        private InvoicingPlatformAccountOwner $accountOwner,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SupplierInvoiceSyncStatus
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->accountOwner->isOwner($user)) {
            throw new AccessDeniedHttpException('La synchronisation Qonto n\'est pas disponible pour ce compte.');
        }

        $this->messageBus->dispatch(
            new SyncSupplierInvoicesMessage((int) $user->getId()),
            [new DeduplicateStamp('supplier-invoice-sync-request-' . $user->getId(), self::DEDUPLICATE_TTL_SECONDS)],
        );

        return new SupplierInvoiceSyncStatus();
    }
}
