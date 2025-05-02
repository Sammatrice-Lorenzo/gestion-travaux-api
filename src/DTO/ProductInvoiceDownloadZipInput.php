<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductInvoiceDownloadZipInput
{
    /**
     * @var int[]
     */
    #[Assert\NotBlank]
    #[Assert\NotNull(message: 'Le paramètre "ids" est obligatoire')]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Type('int'),
    ])]
    public array $ids = [];
}
