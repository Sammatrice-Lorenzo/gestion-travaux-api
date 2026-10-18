<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

use DateTimeImmutable;

/**
 * Bilan d'une synchronisation des factures fournisseurs.
 */
final class SupplierInvoiceSyncResult
{
    public int $created = 0;

    public int $updated = 0;

    /**
     * Factures exclues (statut, avoir, document non financier, sans pièce jointe, format non pris en charge) ou inchangées.
     */
    public int $skipped = 0;

    /**
     * @var list<string> pièces jointes ignorées car dans un format non pris en charge (HEIC...), à traiter à la main
     */
    public array $unsupported = [];

    /**
     * @var list<string> factures en erreur : elles seront retentées à la prochaine synchro
     */
    public array $errors = [];

    /**
     * Fin de la fenêtre synchronisée, null si le point de reprise n'a pas avancé (erreurs).
     */
    public ?DateTimeImmutable $syncedUntil = null;

    /**
     * @var list<string> identifiants plateforme des factures créées
     */
    public array $createdIds = [];

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }
}
