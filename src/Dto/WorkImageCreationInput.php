<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class WorkImageCreationInput
{
    #[NotNull(message: 'L\'identifiant de la presttion est obligatoire !')]
    public int $workId;

    /**
     * @var UploadedFile[]
     */
    #[Assert\Count(min: 1, minMessage: 'Veuillez insérer au moins une image !')]
    #[Assert\All([
        new Assert\File(mimeTypes: ['image/jpeg', 'image/png', 'image/webp']),
    ])]
    public array $images = [];
}
