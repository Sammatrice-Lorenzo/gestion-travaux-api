<?php

namespace App\Message;

final class ParseProductInvoiceFileMessage
{
    public function __construct(
        public readonly int $invoiceFileId
    ) {}
}
