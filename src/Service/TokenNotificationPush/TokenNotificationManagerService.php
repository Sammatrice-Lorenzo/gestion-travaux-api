<?php

namespace App\Service\TokenNotificationPush;

use App\Entity\TokenNotificationPush;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

final class TokenNotificationManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private Security $security,
        private SerializerInterface $serializerInterface
    ) {}

    private function saveTokenNotificationPush(
        TokenNotificationPush $tokenNotificationPushRequest,
        ?TokenNotificationPush $tokenNotificationPush
    ): TokenNotificationPush {
        $userAgent = $tokenNotificationPushRequest->getUserAgent();
        $token = $tokenNotificationPushRequest->getToken();

        /** * @var User $user  */
        $user = $this->security->getUser();

        if (!$tokenNotificationPush) {
            $tokenNotificationPush = (new TokenNotificationPush())
                ->setUser($user)
                ->setToken(token: $token)
                ->setUserAgent($userAgent)
            ;
            $this->entityManagerInterface->persist($tokenNotificationPush);
        } elseif ($tokenNotificationPush->getToken() !== $token) {
            $tokenNotificationPush->setToken($token);
        }

        $this->entityManagerInterface->flush();

        return $tokenNotificationPush;
    }

    public function deserializeRequestContent(Request $request): TokenNotificationPush
    {
        return $this->serializerInterface->deserialize(
            $request->getContent(),
            TokenNotificationPush::class,
            'json'
        );
    }

    public function serializeTokenNotificationPush(TokenNotificationPush $tokenNotificationPushRequest, ?TokenNotificationPush $tokenNotificationPush): string
    {
        $tokenNotificationPushUpdated = $this->saveTokenNotificationPush($tokenNotificationPushRequest, $tokenNotificationPush);

        return $this->serializerInterface->serialize($tokenNotificationPushUpdated, 'json', [
            'groups' => TokenNotificationPush::GROUP_TOKEN_NOTIFICATION_PUSH_READ,
        ]);
    }
}
