<?php

namespace App\Dto;

use App\Entity\ProductInvoiceFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductInvoiceUpdateInput
{
    #[Assert\NotBlank]
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide')]
    #[Groups([ProductInvoiceFile::GROUP_PRODUCT_INVOICE_FILE_WRITE])]
    public string $date;

    #[Assert\NotBlank(message: 'Le total doit être saisi')]
    #[Assert\Type('numeric')]
    #[Groups([ProductInvoiceFile::GROUP_PRODUCT_INVOICE_FILE_WRITE])]
    public float $totalAmount;

    #[Assert\NotBlank(message: 'Le nom de la facture doit être saisi')]
    #[Assert\Type('string')]
    #[Groups([ProductInvoiceFile::GROUP_PRODUCT_INVOICE_FILE_WRITE])]
    public string $name;
}
