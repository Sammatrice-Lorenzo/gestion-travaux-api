<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class WorkEventDayDownloadFileInput
{
    #[Assert\NotNull(message: 'La date doit être saisie')]
    #[Assert\NotBlank(message: 'La date doit être saisie')]
    #[Assert\Date(message: 'Format de date invalide')]
    public string $date;
}
