<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\SupplierReturnInvoiceFile;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierReturnInvoiceUpdateInput
{
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide')]
    #[Groups([SupplierReturnInvoiceFile::GROUP_WRITE])]
    public string $date;

    #[Assert\NotBlank(message: 'Le montant doit être saisi')]
    #[Assert\Type('numeric')]
    #[Groups([SupplierReturnInvoiceFile::GROUP_WRITE])]
    public float $creditAmount;

    #[Assert\NotBlank(message: 'Le nom doit être saisi')]
    #[Assert\Type('string')]
    #[Groups([SupplierReturnInvoiceFile::GROUP_WRITE])]
    public string $name;

    #[Groups([SupplierReturnInvoiceFile::GROUP_WRITE])]
    public ?int $supplierId = null;

    #[Groups([SupplierReturnInvoiceFile::GROUP_WRITE])]
    public ?int $linkedProductInvoiceId = null;
}
