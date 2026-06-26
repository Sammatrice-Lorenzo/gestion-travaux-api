<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SupplierReturnInvoiceDownloadZipInput
{
    /**
     * @var int[]
     */
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins une facture !')]
    public array $ids = [];
}
