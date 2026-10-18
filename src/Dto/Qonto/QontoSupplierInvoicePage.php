<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

final readonly class QontoSupplierInvoicePage
{
    /**
     * @param list<QontoSupplierInvoice> $invoices
     */
    public function __construct(
        public array $invoices,
        public QontoPaginationMeta $meta,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromArray(array $response): self
    {
        $invoices = [];
        foreach (is_array($response['supplier_invoices'] ?? null) ? $response['supplier_invoices'] : [] as $invoice) {
            if (is_array($invoice) && isset($invoice['id'])) {
                /** @var array<string, mixed> $invoice */
                $invoices[] = QontoSupplierInvoice::fromArray($invoice);
            }
        }

        /** @var array<string, mixed> $meta */
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        return new self($invoices, QontoPaginationMeta::fromArray($meta));
    }
}
