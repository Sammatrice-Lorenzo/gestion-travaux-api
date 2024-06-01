<?php

namespace App\Interface;

use setasign\Fpdi\Fpdi;

interface InvoiceFileInterface
{
    public function setFpdi(Fpdi $fpdi): void;
}
