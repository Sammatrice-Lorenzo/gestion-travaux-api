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
     * Factures exclues (statut, avoir, document non financier, sans pièce jointe, format ou devise non pris en charge) ou inchangées.
     */
    public int $skipped = 0;

    /**
     * @var list<string> pièces jointes ignorées car dans un format ou une devise non pris en charge, à traiter à la main
     */
    public array $unsupported = [];

    /**
     * @var list<string> erreurs de la plateforme (réseau, indisponibilité) : retentées, le point de reprise n'avance pas
     */
    public array $errors = [];

    /**
     * @var list<string> erreurs permanentes (donnée invalide, fichier...) : signalées sans bloquer les synchros suivantes
     */
    public array $failures = [];

    /**
     * Fin de la fenêtre synchronisée, null si le point de reprise n'a pas avancé (erreurs de la plateforme).
     */
    public ?DateTimeImmutable $syncedUntil = null;

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    public function hasFailures(): bool
    {
        return [] !== $this->failures;
    }

    /**
     * Aucune erreur ni échec : toutes les factures de la fenêtre ont été traitées.
     */
    public function isSuccessful(): bool
    {
        return !$this->hasErrors() && !$this->hasFailures();
    }
}
