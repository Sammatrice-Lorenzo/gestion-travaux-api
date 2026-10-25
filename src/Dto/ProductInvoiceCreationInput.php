<?php

declare(strict_types=1);

namespace App\Dto;

use App\Service\ProductInvoiceFile\UploadedInvoiceFileConverter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductInvoiceCreationInput
{
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide !')]
    public string $date;

    /**
     * PDF ou photo (JPEG, PNG, WebP), convertie en PDF à l'enregistrement.
     *
     * @var UploadedFile[]
     */
    #[Assert\Count(min: 1, minMessage: 'Veuillez insérer au moins un fichier !')]
    #[Assert\All([
        new Assert\File(
            mimeTypes: [...UploadedInvoiceFileConverter::PDF_MIME_TYPES, ...UploadedInvoiceFileConverter::IMAGE_MIME_TYPES],
            mimeTypesMessage: 'Seuls les fichiers PDF et les photos (JPEG, PNG, WebP) sont acceptés.',
        ),
    ])]
    public array $files = [];
}
