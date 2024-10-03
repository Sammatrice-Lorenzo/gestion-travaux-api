<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Controller\WorkEventDayController;
use App\Repository\WorkEventDayRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\CssColor;
use Symfony\Component\Validator\Constraints\NotBlank;


#[ORM\Entity(repositoryClass: WorkEventDayRepository::class)]
#[ApiResource(
    security: 'is_granted("ROLE_USER")',
    operations: [
        new GetCollection(
            uriTemplate: '/work/event/day/{id}',
            controller: WorkEventDayController::class,
            read: false,
            security: 'is_granted("ROLE_USER")',
            openapi: new Operation(
                security: [['bearerAuth' => []]],
                parameters: [
                    new Parameter(
                        name: 'id',
                        in: 'path',
                        required: true,
                        description: 'The user ID',
                        schema: ['type' => 'string']
                    )
                ]
            )
        )
    ],
    normalizationContext: ['groups' => ['read:EventDay']],
)]
#[ApiResource]
class WorkEventDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:EventDay'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[NotBlank]
    #[Groups(['read:EventDay'])]
    private string $title;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:EventDay'])]
    private DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:EventDay'])]
    private DateTimeInterface $endDate;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[NotBlank]
    #[CssColor(message: 'Le code couleur {{ value }} ne correspond pas à un code valide')]
    #[Groups(['read:EventDay'])]
    private string $color;

    #[ORM\ManyToOne(inversedBy: 'workEventDays')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:EventDay'])]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(readableLink: true)]
    private ?Client $client = null;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getTitle(): string
    {
        return $this->title;
    }

    final public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    final public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    final public function setStartDate(DateTimeInterface $date): static
    {
        $this->startDate = $date;

        return $this;
    }

    final public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    final public function setEndDate(DateTimeInterface $date): static
    {
        $this->endDate = $date;

        return $this;
    }

    final public function getColor(): string
    {
        return $this->color;
    }

    final public function setColor(string $color): static
    {
        $this->color = $color;

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

    #[Groups(['read:EventDay'])]
    final public function getClient(): ?Client
    {
        return $this->client;
    }

    final public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
