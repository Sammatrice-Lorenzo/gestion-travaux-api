<?php

namespace App\Controller;

use App\Entity\TokenNotificationPush;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\TokenNotificationPushRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\TokenNotificationPush\TokenNotificationManagerService;

final class RegisterTokenNotificationPushController extends AbstractController
{
    public function __construct(
        private readonly TokenNotificationPushRepository $tokenNotificationPushRepository,
        private readonly TokenNotificationManagerService $tokenNotificationPushManagerService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $tokenNotificationPushData = $this->tokenNotificationPushManagerService->deserializeRequestContent($request);

        /** * @var User $user  */
        $user = $this->getUser();

        $userAgent = $tokenNotificationPushData->getUserAgent();
        $token = $tokenNotificationPushData->getToken();
        /** @var ?TokenNotificationPush $tokenNotificationPush */
        $tokenNotificationPush = $this->tokenNotificationPushRepository->findOneBy(['user' => $user, 'userAgent' => $userAgent]);

        if ($tokenNotificationPush && $tokenNotificationPush->getToken() === $token) {
            return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT, [], true);
        }

        $data = $this->tokenNotificationPushManagerService->serializeTokenNotificationPush(
            $tokenNotificationPushData,
            $tokenNotificationPush
        );

        return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
    }
}
