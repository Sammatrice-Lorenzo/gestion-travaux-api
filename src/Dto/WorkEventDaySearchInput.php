<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WorkEventDaySearchInput
{
    #[Assert\Type('integer')]
    public ?int $client = null;

    #[Assert\NotBlank(message: 'Le motif de recherche est requis')]
    #[Assert\Length(max: 200, maxMessage: 'Le motif de recherche est trop long (200 caractères maximum)')]
    public string $search;

    #[Assert\NotBlank(message: 'La date de début doit être saisie')]
    #[Assert\Date(message: 'Format de date de début invalide')]
    public string $startDate;

    #[Assert\NotBlank(message: 'La date de fin doit être saisie')]
    #[Assert\Date(message: 'Format de date de fin invalide')]
    public string $endDate;
}
