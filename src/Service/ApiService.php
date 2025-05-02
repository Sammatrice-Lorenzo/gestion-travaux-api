<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ApiService
{
    public static function getErrorToken(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'Token not found.',
        ], Response::HTTP_UNAUTHORIZED);
    }

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

    public static function getRequestToken(Request $request): ?string
    {
        return $request->headers->get('authorization');
    }
}
