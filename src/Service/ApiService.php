<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ApiService
{
    /**
     * @param string $errors
     *
     * @return JsonResponse
     */
    public static function getJsonResponseRequestParameters(array $errors): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }
}
