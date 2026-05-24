<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final readonly class ApiErrorsService
{
    /**
     * @return array<string|\Stringable>
     */
    public static function getErrorsSeralizationInput(ConstraintViolationListInterface $errors): array
    {
        $errorsMessage = [];
        foreach ($errors as $error) {
            $errorsMessage[] = $error->getMessage();
        }

        return $errorsMessage;
    }
}
