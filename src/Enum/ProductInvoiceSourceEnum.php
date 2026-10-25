<?php

declare(strict_types=1);

namespace App\Enum;

enum ProductInvoiceSourceEnum: string
{
    case MANUAL = 'manual';
    case QONTO = 'qonto';
}
