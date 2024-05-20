<?php

namespace App\Service;

use stdClass;

final readonly class InvoiceFormService
{
    /**
     * @param stdClass $invoiceData
     * @return string[]
     */
    public function checkInvoiceData(stdClass $invoiceData): array
    {
        /** @var string[] $errorMessage */
        $errorMessage = [];
        
        /** @var string[] $properties */
        $properties = ['nameInvoice', 'invoicesLines', 'idClient'];

        foreach ($properties as $property) {
            if (!property_exists($invoiceData, $property)) {
                $errorMessage[] = "Le champ {$property} est obligatoire";
            }
        }

        return $errorMessage;
    }
}
