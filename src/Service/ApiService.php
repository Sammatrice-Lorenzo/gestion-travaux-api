<?php

namespace App\Service;

use stdClass;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiService
{
    public function getErrorToken(): JsonResponse
    {
        return new JsonResponse([
            'code' => '401',
            'error' => 'Token not found.'
        ], 401);
    }

    public function getErrorUser(): JsonResponse
    {
        return new JsonResponse([
            'code' => '404',
            'error' => 'Utilisateur non trouvé.'
        ], 404);
    }

    public function getDecodedTokenByString(string $token): stdClass
    {
        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        return $jwtPayload;
    }

    public function isValidTokenString(User $user, string $token): bool
    {
        return $this->getDecodedTokenByString($token)->email === $user->getEmail();
    }

}
