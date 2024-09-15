<?php

namespace App\Service;

use stdClass;
use App\Entity\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ApiService
{
    private const string INVALID_TOKEN = 'Invalid token';

    public static function getErrorToken(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'Token not found.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public static function getErrorUser(): JsonResponse
    {
        return new JsonResponse([
            'code' => Response::HTTP_NOT_FOUND,
            'error' => 'Utilisateur non trouvé.'
        ], Response::HTTP_NOT_FOUND);
    }

    public static function getToken(string $token): string
    {
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        return $token;
    }

    public static function getDecodedTokenByString(string $token): stdClass
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new InvalidArgumentException(self::INVALID_TOKEN);
        }
    
        $tokenPayload = base64_decode($tokenParts[1]);
    
        if ($tokenPayload === false) {
            throw new InvalidArgumentException(self::INVALID_TOKEN . ' payload');
        }
    
        $decodedPayload = json_decode($tokenPayload, true);

        if ($decodedPayload === null) {
            throw new InvalidArgumentException(self::INVALID_TOKEN . 'payload JSON');
        }
    
        return json_decode($tokenPayload);
    }

    public static function isValidTokenString(?User $user, string $token): bool
    {
        return self::getDecodedTokenByString($token)->email === $user?->getEmail();
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

    public static function getRequestToken(Request $request): string
    {
        return $request->headers->get('authorization');
    }
}
