<?php

namespace App\Controller;

use App\Entity\TokenNotificationPush;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $response = new JsonResponse();

        $userAgent = $tokenNotificationPushData->getUserAgent();
        $token = $tokenNotificationPushData->getToken();
        /** @var ?TokenNotificationPush $tokenNotificationPush */
        $tokenNotificationPush = $this->tokenNotificationPushRepository->findOneBy(['user' => $user, 'userAgent' => $userAgent]);

        if ($tokenNotificationPush && $tokenNotificationPush->getToken() === $token) {
            return $response->setStatusCode(Response::HTTP_NO_CONTENT);
        }

        $data = $this->tokenNotificationPushManagerService->serializeTokenNotificationPush(
            $tokenNotificationPushData,
            $tokenNotificationPush
        );

        return $response
            ->setData($data)
            ->setStatusCode(Response::HTTP_OK)
        ;
    }
}
