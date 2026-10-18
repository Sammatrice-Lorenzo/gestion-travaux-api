<?php

declare(strict_types=1);

namespace App\Service\Qonto;

use App\Dto\InvoicingPlatform\PlatformAttachment;
use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;
use App\Dto\Qonto\QontoPaginationMeta;
use App\Dto\Qonto\QontoSupplierInvoicePage;
use App\Exceptions\QontoApiException;
use DateTimeImmutable;

/**
 * Traduit les réponses JSON de l'API Qonto vers les DTO neutres de la plateforme agréée.
 */
final readonly class QontoPayloadMapper
{
    /**
     * @param array<string, mixed> $response réponse de GET /v2/supplier_invoices
     */
    public function toSupplierInvoicePage(array $response): QontoSupplierInvoicePage
    {
        $invoices = [];
        $skippedCount = 0;
        foreach (is_array($response['supplier_invoices'] ?? null) ? $response['supplier_invoices'] : [] as $invoice) {
            if (!is_array($invoice) || null === $this->stringOrNull($invoice['id'] ?? null)) {
                ++$skippedCount;

                continue;
            }

            /** @var array<string, mixed> $invoice */
            $invoices[] = $this->toSupplierInvoice($invoice);
        }

        /** @var array<string, mixed> $meta */
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        return new QontoSupplierInvoicePage($invoices, $this->toPaginationMeta($meta), $skippedCount);
    }

    /**
     * @param array<string, mixed> $response réponse de GET /v2/attachments/{id}
     *
     * @throws QontoApiException si la réponse ne contient pas de pièce jointe exploitable (id et url requis)
     */
    public function toAttachment(array $response): PlatformAttachment
    {
        /** @var array<string, mixed> $attachment */
        $attachment = is_array($response['attachment'] ?? null) ? $response['attachment'] : [];

        $id = $this->stringOrNull($attachment['id'] ?? null);
        $url = $this->stringOrNull($attachment['url'] ?? null);
        if (null === $id || null === $url) {
            throw new QontoApiException('Qonto : réponse de pièce jointe inattendue (id ou url manquant).');
        }

        return new PlatformAttachment(
            id: $id,
            fileName: $this->stringOrNull($attachment['file_name'] ?? null) ?? '',
            fileContentType: $this->stringOrNull($attachment['file_content_type'] ?? null) ?? '',
            url: $url,
        );
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function toSupplierInvoice(array $invoice): PlatformSupplierInvoice
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = is_array($invoice['supplier_snapshot'] ?? null) ? $invoice['supplier_snapshot'] : [];
        /** @var array<string, mixed> $totalAmount */
        $totalAmount = is_array($invoice['total_amount'] ?? null) ? $invoice['total_amount'] : [];
        /** @var array<string, mixed> $seller */
        $seller = is_array($invoice['seller'] ?? null) ? $invoice['seller'] : [];
        /** @var array<string, mixed> $sellerAddress */
        $sellerAddress = is_array($seller['address'] ?? null) ? $seller['address'] : [];

        return new PlatformSupplierInvoice(
            id: (string) $this->stringOrNull($invoice['id'] ?? null),
            status: $this->stringOrNull($invoice['status'] ?? null) ?? '',
            fileName: $this->stringOrNull($invoice['file_name'] ?? null) ?? '',
            attachmentId: $this->stringOrNull($invoice['attachment_id'] ?? null),
            invoiceNumber: $this->stringOrNull($invoice['invoice_number'] ?? null),
            supplierId: $this->firstStringOrNull($invoice['supplier_id'] ?? null, $snapshot['id'] ?? null),
            supplierName: $this->firstStringOrNull($invoice['supplier_name'] ?? null, $snapshot['name'] ?? null, $seller['name'] ?? null, $invoice['issuer_name'] ?? null),
            vatNumber: $this->firstStringOrNull($snapshot['vat_number'] ?? null, $invoice['vat_number'] ?? null, $seller['vat_number'] ?? null),
            totalAmount: isset($totalAmount['value']) && is_numeric($totalAmount['value']) ? (float) $totalAmount['value'] : null,
            currency: $this->stringOrNull($totalAmount['currency'] ?? null),
            issueDate: $this->dateOrNull($invoice['issue_date'] ?? null),
            updatedAt: $this->dateOrNull($invoice['updated_at'] ?? null),
            isCreditNote: true === ($invoice['is_credit_note'] ?? false),
            isNonFinancial: true === ($invoice['is_attachment_non_financial'] ?? false),
            supplierStreetAddress: $this->stringOrNull($sellerAddress['street_address'] ?? null),
            supplierZipCode: $this->stringOrNull($sellerAddress['zip_code'] ?? null),
            supplierCity: $this->stringOrNull($sellerAddress['city'] ?? null),
            supplierCountryCode: $this->stringOrNull($sellerAddress['country_code'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function toPaginationMeta(array $meta): QontoPaginationMeta
    {
        return new QontoPaginationMeta(
            currentPage: (int) ($meta['current_page'] ?? 1),
            nextPage: isset($meta['next_page']) ? (int) $meta['next_page'] : null,
            totalPages: (int) ($meta['total_pages'] ?? 1),
            totalCount: (int) ($meta['total_count'] ?? 0),
            perPage: (int) ($meta['per_page'] ?? 0),
        );
    }

    /**
     * Première valeur non vide (après trim) : une chaîne vide ne masque pas les sources suivantes.
     */
    private function firstStringOrNull(mixed ...$values): ?string
    {
        $strings = array_filter(array_map($this->stringOrNull(...), $values), static fn (?string $value): bool => null !== $value);

        return [] === $strings ? null : reset($strings);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function dateOrNull(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        $date = date_create_immutable($value);

        return false === $date ? null : $date;
    }
}
