<?php

namespace App\Checker\ProductInvoice;

use App\Service\ApiService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


final readonly class ProductInvoiceRequestChecker
{
    private const string NOT_FOUND_DATE_PARAMETER = 'Le champ date est manquant';

    public function __construct(
        private Security $security
    ) {
    }

    public function checkBodyAPI(Request $request): JsonResponse
    {
        $token = ApiService::getRequestToken($request);
        $date = $request->request->get('date');
        $files = $request->files->all();

        if (!$token) {
            return ApiService::getErrorToken();
        }

        $errorMessage = [];
        if (empty($files)) {
            $errorMessage['error'][] = "Veuillez insérer un ou plusieurs fichiers";
        }

        if (!$date) {
            $errorMessage['error'][] = self::NOT_FOUND_DATE_PARAMETER;
        }

        $isValidToken = ApiService::isValidTokenString($this->security->getUser(), $token);
        if (!$isValidToken) {
            $errorMessage['error'] = "Utilisateur pas autorisé";
        }

        return new JsonResponse($errorMessage, Response::HTTP_FORBIDDEN);
    }

    public function handleErrorToken(Request $request): ?JsonResponse
    {
        $token = ApiService::getRequestToken($request);
        $isValidToken = ApiService::isValidTokenString($this->security->getUser(), $token);

        if (!$token || !$isValidToken) {
            $responseToken = ApiService::getErrorToken();
            $responseUser = ApiService::getErrorUser();

            return !$token ? $responseToken : $responseUser;
        }

        return null;
    }

    public function handleApiGetError(Request $request): JsonResponse
    {
        $date = $request->query->get('date');
        if ($this->handleErrorToken($request)) {
            return $this->handleErrorToken($request);
        }

        $errorMessage = [];
        if (!$date) {
            $errorMessage['error'] = self::NOT_FOUND_DATE_PARAMETER;
        }

        return new JsonResponse($errorMessage, Response::HTTP_FORBIDDEN);
    }
}
