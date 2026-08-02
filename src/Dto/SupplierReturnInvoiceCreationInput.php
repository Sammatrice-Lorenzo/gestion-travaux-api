<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierReturnInvoiceCreationInput
{
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide !')]
    public string $date;

    public ?int $supplierId = null;

    /**
     * @var UploadedFile[]
     */
    #[Assert\Count(min: 1, minMessage: 'Veuillez insérer au moins un fichier !')]
    #[Assert\All([
        new Assert\File(mimeTypes: ['application/pdf', 'application/x-pdf']),
    ])]
    public array $files = [];
}
