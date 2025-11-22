<?php

namespace App\Dto;

final class ProductInvoiceResponseIA
{
    public function __construct(
        public readonly string $total_amount,
        public readonly string $invoice_date,
        public readonly SupplierDto $supplier
    ) {}
}
