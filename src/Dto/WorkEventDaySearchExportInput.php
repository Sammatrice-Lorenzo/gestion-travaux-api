<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class WorkEventDaySearchExportInput extends WorkEventDaySearchInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['pdf', 'xlsx'], message: 'Format d\'export invalide')]
    public string $format = 'pdf';
}
