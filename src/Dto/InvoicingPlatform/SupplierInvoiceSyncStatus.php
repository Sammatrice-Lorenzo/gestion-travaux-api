<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

/**
 * Réponse à une demande de synchro des factures fournisseurs.
 */
final readonly class SupplierInvoiceSyncStatus
{
    public const string STARTED = 'Synchronisation lancée.';

    public function __construct(
        public string $message = self::STARTED,
    ) {}
}
