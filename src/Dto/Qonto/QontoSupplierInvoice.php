<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

use DateTimeImmutable;

/**
 * Facture fournisseur renvoyée par GET /v2/supplier_invoices (champs utiles à l'import uniquement).
 */
final readonly class QontoSupplierInvoice
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

    /**
     * @param array<string, mixed> $invoice
     */
    public static function fromArray(array $invoice): self
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = is_array($invoice['supplier_snapshot'] ?? null) ? $invoice['supplier_snapshot'] : [];
        /** @var array<string, mixed> $totalAmount */
        $totalAmount = is_array($invoice['total_amount'] ?? null) ? $invoice['total_amount'] : [];
        /** @var array<string, mixed> $seller */
        $seller = is_array($invoice['seller'] ?? null) ? $invoice['seller'] : [];
        /** @var array<string, mixed> $sellerAddress */
        $sellerAddress = is_array($seller['address'] ?? null) ? $seller['address'] : [];

        return new self(
            id: (string) $invoice['id'],
            status: (string) ($invoice['status'] ?? ''),
            fileName: (string) ($invoice['file_name'] ?? ''),
            attachmentId: self::stringOrNull($invoice['attachment_id'] ?? null),
            invoiceNumber: self::stringOrNull($invoice['invoice_number'] ?? null),
            supplierId: self::stringOrNull($invoice['supplier_id'] ?? $snapshot['id'] ?? null),
            supplierName: self::stringOrNull($invoice['supplier_name'] ?? $snapshot['name'] ?? $seller['name'] ?? $invoice['issuer_name'] ?? null),
            vatNumber: self::stringOrNull($snapshot['vat_number'] ?? $invoice['vat_number'] ?? $seller['vat_number'] ?? null),
            totalAmount: isset($totalAmount['value']) && is_numeric($totalAmount['value']) ? (float) $totalAmount['value'] : null,
            currency: self::stringOrNull($totalAmount['currency'] ?? null),
            issueDate: self::dateOrNull($invoice['issue_date'] ?? null),
            updatedAt: self::dateOrNull($invoice['updated_at'] ?? null),
            isCreditNote: true === ($invoice['is_credit_note'] ?? false),
            isNonFinancial: true === ($invoice['is_attachment_non_financial'] ?? false),
            supplierStreetAddress: self::stringOrNull($sellerAddress['street_address'] ?? null),
            supplierZipCode: self::stringOrNull($sellerAddress['zip_code'] ?? null),
            supplierCity: self::stringOrNull($sellerAddress['city'] ?? null),
            supplierCountryCode: self::stringOrNull($sellerAddress['country_code'] ?? null),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private static function dateOrNull(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        $date = date_create_immutable($value);

        return false === $date ? null : $date;
    }
}
