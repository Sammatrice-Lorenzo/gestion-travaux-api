<?php

namespace App\Formatter;

use App\Entity\ProductInvoiceFile;

final class ProductInvoiceFormatter
{
    /**
     * @param ProductInvoiceFile[] $workEventDays
     * @return array<string,  string|int|array<int, ProductInvoiceFile>>
     */
    public static function getResponseProductInvoice(array $productInvoiceFiles): array
    {
        return [
            '@id' => '/api/product_invoice/month',
            '@type' => 'ProductInvoice',
            'hydra:member' => $productInvoiceFiles,
            'hydra:totalItems' => count($productInvoiceFiles),
        ];
    }
}
