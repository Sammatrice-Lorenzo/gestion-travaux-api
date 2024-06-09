<?php

namespace App\Service;

use stdClass;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ApiService
{
    public function getErrorToken(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'Token not found.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function getErrorUser(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_NOT_FOUND,
            'error' => 'Utilisateur non trouvé.'
        ], Response::HTTP_NOT_FOUND);
    }

    public function getToken(string $token): string
    {
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        return $token;
    }

    public function getDecodedTokenByString(string $token): stdClass
    {
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        return json_decode($tokenPayload);
    }

    public function isValidTokenString(User $user, string $token): bool
    {
        return $this->getDecodedTokenByString($token)->email === $user->getEmail();
    }

    /**
     * @param string $errors
     * @return JsonResponse
     */
    public static function getJsonResponseRequestParameters(array $errors): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }

    public static function getJsonResponseSuccessForRegistrationUser(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_OK,
            'message' => 'created user',
            'success' => true,
        ], Response::HTTP_OK);
    }
}
