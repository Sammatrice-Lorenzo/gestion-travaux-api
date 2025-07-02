<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use App\Repository\TokenNotificationPushRepository;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Controller\RegisterTokenNotificationPushController;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TokenNotificationPushRepository::class)]
#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    normalizationContext: ['groups' => ['token_notification_push:read']],
    denormalizationContext: ['groups' => ['token_notification_push:write']],
    operations: [
        new Post(
            security: "is_granted('ROLE_USER')",
            controller: RegisterTokenNotificationPushController::class,
        ),
    ]
)]
class TokenNotificationPush
{
    public const string GROUP_TOKEN_NOTIFICATION_PUSH_WRITE = 'token_notification_push:write';

    public const string GROUP_TOKEN_NOTIFICATION_PUSH_READ = 'token_notification_push:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_TOKEN_NOTIFICATION_PUSH_WRITE])]
    private string $token;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_TOKEN_NOTIFICATION_PUSH_WRITE, self::GROUP_TOKEN_NOTIFICATION_PUSH_READ])]
    private string $userAgent;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[NotNull]
    private User $user;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getToken(): string
    {
        return $this->token;
    }

    final public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    final public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    final public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    final public function getUser(): User
    {
        return $this->user;
    }

    final public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
