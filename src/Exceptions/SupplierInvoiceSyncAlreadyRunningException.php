<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Une synchro des factures fournisseurs est déjà en cours pour cet utilisateur (tâche planifiée ou lancement manuel).
 */
final class SupplierInvoiceSyncAlreadyRunningException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Une synchro des factures fournisseurs est déjà en cours pour cet utilisateur.');
    }
}
