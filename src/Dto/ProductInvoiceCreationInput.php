<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductInvoiceCreationInput
{
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide !')]
    public string $date;

    /**
     * @var UploadedFile[]
     */
    #[Assert\Count(min: 1, minMessage: 'Veuillez insérer au moins un fichier !')]
    #[Assert\All([
        new Assert\File(mimeTypes: ['application/pdf', 'application/x-pdf']),
    ])]
    public array $files = [];
}
