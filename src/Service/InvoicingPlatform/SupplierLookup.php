<?php

declare(strict_types=1);

namespace App\Service\InvoicingPlatform;

use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Entity\Supplier;
use Symfony\Component\String\UnicodeString;

/**
 * Fournisseurs d'un utilisateur indexés une seule fois pour toute une synchro (identifiant plateforme, n° de TVA,
 * nom normalisé), au lieu d'une requête et d'une normalisation complète par facture.
 */
final class SupplierLookup
{
    /**
     * @var array<string, Supplier>
     */
    private array $byPlatformId = [];

    /**
     * @var array<string, Supplier>
     */
    private array $byVatNumber = [];

    /**
     * @var array<string, Supplier>
     */
    private array $byName = [];

    /**
     * @param iterable<Supplier> $suppliers
     */
    public function __construct(iterable $suppliers = [])
    {
        foreach ($suppliers as $supplier) {
            $this->add($supplier);
        }
    }

    /**
     * Ordre de priorité : identifiant plateforme, n° de TVA, puis nom normalisé.
     */
    public function find(PlatformSupplierInvoice $invoice): ?Supplier
    {
        return $this->byPlatformId[$invoice->supplierId ?? '']
            ?? $this->byVatNumber[self::normalizeVatNumber($invoice->vatNumber ?? '')]
            ?? $this->byName[self::normalizeName($invoice->supplierName ?? '')]
            ?? null;
    }

    /**
     * Indexe (ou réindexe après complétion) un fournisseur ; à clé égale, le premier fournisseur indexé est conservé.
     */
    public function add(Supplier $supplier): void
    {
        $this->index($this->byPlatformId, $supplier->getQontoSupplierId() ?? '', $supplier);
        $this->index($this->byVatNumber, self::normalizeVatNumber($supplier->getVatNumber() ?? ''), $supplier);
        $this->index($this->byName, self::normalizeName($supplier->getName()), $supplier);
    }

    public static function normalizeName(string $name): string
    {
        return (new UnicodeString($name))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->collapseWhitespace()
            ->trim()
            ->toString()
        ;
    }

    public static function normalizeVatNumber(string $vatNumber): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $vatNumber));
    }

    /**
     * @param array<string, Supplier> $index
     */
    private function index(array &$index, string $key, Supplier $supplier): void
    {
        if ('' !== $key) {
            $index[$key] ??= $supplier;
        }
    }
}
