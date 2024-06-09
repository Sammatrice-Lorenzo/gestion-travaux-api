<?php

namespace App\Checker\WorkEventDay;

use Exception;
use App\Service\ApiService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final readonly class WorkEventDayFileAPIChecker
{
    public function __construct(
        private ApiService $apiService,
        private Security $security
    ) {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @throws AuthenticationException
     */
    public function checkBodyAPI(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        $tokenRequest = $request->headers->get('authorization');

        if (!$tokenRequest) {
            return $this->apiService->getErrorToken();
        }

        /** @var string[] $properties */
        $properties = ['date'];

        $errorMessage = [];
        foreach ($properties as $property) {
            if (!property_exists($data, $property)) {
                $errorMessage[] = "Le champ {$property} est obligatoire";
            }
        }

        return new JsonResponse($errorMessage, Response::HTTP_FORBIDDEN);
    }
}
