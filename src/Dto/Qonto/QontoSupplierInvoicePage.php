<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

final readonly class QontoSupplierInvoicePage
{
    /**
     * @param list<QontoSupplierInvoice> $invoices
     * @param int                        $skippedCount entrées ignorées car sans identifiant (impossibles à importer)
     */
    public function __construct(
        public array $invoices,
        public QontoPaginationMeta $meta,
        public int $skippedCount = 0,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromArray(array $response): self
    {
        $invoices = [];
        $skippedCount = 0;
        foreach (is_array($response['supplier_invoices'] ?? null) ? $response['supplier_invoices'] : [] as $invoice) {
            if (!is_array($invoice) || !is_scalar($invoice['id'] ?? null) || '' === trim((string) $invoice['id'])) {
                ++$skippedCount;

                continue;
            }

            /** @var array<string, mixed> $invoice */
            $invoices[] = QontoSupplierInvoice::fromArray($invoice);
        }

        /** @var array<string, mixed> $meta */
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        return new self($invoices, QontoPaginationMeta::fromArray($meta), $skippedCount);
    }
}
