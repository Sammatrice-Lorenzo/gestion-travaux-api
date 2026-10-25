<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

use DateTimeImmutable;

/**
 * Facture fournisseur reçue sur la plateforme agréée (champs utiles à l'import uniquement).
 * Les identifiants (id, attachmentId, supplierId) sont ceux de la plateforme.
 */
final readonly class PlatformSupplierInvoice
{
    public function __construct(
        public string $id,
        public string $status,
        public string $fileName,
        public ?string $attachmentId,
        public ?string $invoiceNumber,
        public ?string $supplierId,
        public ?string $supplierName,
        public ?string $vatNumber,
        public ?float $totalAmount,
        public ?string $currency,
        public ?DateTimeImmutable $issueDate,
        public ?DateTimeImmutable $updatedAt,
        public bool $isCreditNote,
        public bool $isNonFinancial,
        public ?string $supplierStreetAddress,
        public ?string $supplierZipCode,
        public ?string $supplierCity,
        public ?string $supplierCountryCode,
    ) {}
}
