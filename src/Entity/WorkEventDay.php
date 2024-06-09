<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\WorkEventDayController;
use App\Repository\WorkEventDayRepository;
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
            openapiContext: [
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => 'The user ID',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        )
    ]
)]
#[ApiResource]
class WorkEventDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[NotBlank]
    private string $title;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    private DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    private DateTimeInterface $endDate;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[NotBlank]
    #[CssColor(message: 'Le code couleur {{ value }} ne correspond pas à un code valide')]
    private string $color;

    #[ORM\ManyToOne(inversedBy: 'workEventDays')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Client $client = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $date): static
    {
        $this->startDate = $date;

        return $this;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $date): static
    {
        $this->endDate = $date;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
