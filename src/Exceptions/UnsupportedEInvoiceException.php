<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class UnsupportedEInvoiceException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct(message: "Facture électronique : {$reason}.");
    }
}
