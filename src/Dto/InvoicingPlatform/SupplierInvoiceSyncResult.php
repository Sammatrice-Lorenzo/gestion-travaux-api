<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

use DateTimeImmutable;

/**
 * Bilan d'une synchronisation des factures fournisseurs.
 */
final class SupplierInvoiceSyncResult
{
    public const string UNKNOWN_SUPPLIER = 'Fournisseur inconnu';

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

    /**
     * @var array<string, array{created: int, updated: int, skipped: int, failed: int}> bilan par nom de fournisseur
     */
    public array $suppliers = [];

    public static function supplierLabel(?string $supplierName): string
    {
        $name = trim($supplierName ?? '');

        return '' !== $name ? $name : self::UNKNOWN_SUPPLIER;
    }

    public function addCreated(?string $supplierName): void
    {
        ++$this->created;
        $this->countForSupplier($supplierName, 'created');
    }

    public function addUpdated(?string $supplierName): void
    {
        ++$this->updated;
        $this->countForSupplier($supplierName, 'updated');
    }

    public function addSkipped(?string $supplierName): void
    {
        ++$this->skipped;
        $this->countForSupplier($supplierName, 'skipped');
    }

    public function addError(?string $supplierName, string $error): void
    {
        $this->errors[] = $error;
        $this->countForSupplier($supplierName, 'failed');
    }

    public function addFailure(?string $supplierName, string $failure): void
    {
        $this->failures[] = $failure;
        $this->countForSupplier($supplierName, 'failed');
    }

    /**
     * Bilan complet, conservé dans l'historique des messages traités par le worker.
     *
     * @return array<string, mixed>
     */
    public function toReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'suppliers' => $this->suppliers,
            'unsupported' => $this->unsupported,
            'errors' => $this->errors,
            'failures' => $this->failures,
            'syncedUntil' => $this->syncedUntil,
        ];
    }

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

    /**
     * @param 'created'|'failed'|'skipped'|'updated' $outcome
     */
    private function countForSupplier(?string $supplierName, string $outcome): void
    {
        $name = self::supplierLabel($supplierName);
        $this->suppliers[$name] ??= ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        ++$this->suppliers[$name][$outcome];
    }
}
