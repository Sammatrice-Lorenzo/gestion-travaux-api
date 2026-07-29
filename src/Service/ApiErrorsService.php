<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public static function getHydraDescriptionResponse(string $description): JsonResponse
    {
        return new JsonResponse([
            'hydra:description' => $description,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
