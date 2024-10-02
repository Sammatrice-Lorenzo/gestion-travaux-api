<?php

namespace App\DTO;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductInvoiceUpdateInput
{
    #[Assert\NotBlank]
    #[Assert\Date]
    public DateTimeInterface $date;

    #[Assert\NotBlank]
    #[Assert\Type('numeric')]
    public float $totalAmount;
}
