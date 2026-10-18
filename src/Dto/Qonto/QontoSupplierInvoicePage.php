<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

use App\Dto\InvoicingPlatform\PlatformSupplierInvoice;

/**
 * Page de GET /v2/supplier_invoices : la pagination reste un détail propre à Qonto.
 */
final readonly class QontoSupplierInvoicePage
{
    /**
     * @param list<PlatformSupplierInvoice> $invoices
     * @param int                           $skippedCount entrées ignorées car sans identifiant (impossibles à importer)
     */
    public function __construct(
        public array $invoices,
        public QontoPaginationMeta $meta,
        public int $skippedCount = 0,
    ) {}
}
