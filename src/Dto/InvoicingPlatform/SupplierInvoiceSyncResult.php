<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

use DateTimeImmutable;

/**
 * Bilan d'une synchronisation des factures fournisseurs. Les totaux sont calculés depuis le bilan par fournisseur,
 * pour que les deux ne puissent pas diverger.
 */
final class SupplierInvoiceSyncResult
{
    public const string UNKNOWN_SUPPLIER = 'Fournisseur inconnu';

    /**
     * Fin de la fenêtre synchronisée, null si le point de reprise n'a pas avancé (erreurs de la plateforme).
     */
    public ?DateTimeImmutable $syncedUntil = null;

    /**
     * @var array<string, array{created: int, updated: int, skipped: int, failed: int}> bilan par nom de fournisseur
     */
    private array $suppliers = [];

    /**
     * @var list<string> pièces jointes ignorées car dans un format ou une devise non pris en charge, à traiter à la main
     */
    private array $unsupported = [];

    /**
     * @var list<string> erreurs de la plateforme (réseau, indisponibilité) : retentées, le point de reprise n'avance pas
     */
    private array $errors = [];

    /**
     * @var list<string> erreurs permanentes (donnée invalide, fichier...) : signalées sans bloquer les synchros suivantes
     */
    private array $failures = [];

    public static function supplierLabel(?string $supplierName): string
    {
        $name = trim($supplierName ?? '');

        return '' !== $name ? $name : self::UNKNOWN_SUPPLIER;
    }

    public function addCreated(?string $supplierName): void
    {
        $this->countForSupplier($supplierName, 'created');
    }

    public function addUpdated(?string $supplierName): void
    {
        $this->countForSupplier($supplierName, 'updated');
    }

    /**
     * Facture exclue (statut, avoir, document non financier, sans pièce jointe) ou inchangée.
     */
    public function addSkipped(?string $supplierName): void
    {
        $this->countForSupplier($supplierName, 'skipped');
    }

    public function addUnsupported(?string $supplierName, string $unsupported): void
    {
        $this->unsupported[] = $unsupported;
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

    public function created(): int
    {
        return array_sum(array_column($this->suppliers, 'created'));
    }

    public function updated(): int
    {
        return array_sum(array_column($this->suppliers, 'updated'));
    }

    public function skipped(): int
    {
        return array_sum(array_column($this->suppliers, 'skipped'));
    }

    /**
     * @return array<string, array{created: int, updated: int, skipped: int, failed: int}>
     */
    public function suppliers(): array
    {
        return $this->suppliers;
    }

    /**
     * @return list<string>
     */
    public function unsupported(): array
    {
        return $this->unsupported;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<string>
     */
    public function failures(): array
    {
        return $this->failures;
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
