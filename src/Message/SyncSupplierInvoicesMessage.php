<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Synchro des factures fournisseurs demandée depuis la PWA, traitée par le worker (transport async).
 */
final readonly class SyncSupplierInvoicesMessage
{
    public function __construct(
        public int $userId,
    ) {}
}
