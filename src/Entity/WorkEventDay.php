<?php

namespace App\Entity;

use DateTimeInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use App\State\MonthlyProvider;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Interface\UserOwnerInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Dto\WorkEventDayDownloadFileInput;
use App\Processor\UserAssignmentProcessor;
use App\Repository\WorkEventDayRepository;
use App\Interface\MonthlyProviderInterface;
use App\Controller\WorkEventDayFileController;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\CssColor;
use Symfony\Component\Validator\Constraints\NotBlank;
use ApiPlatform\OpenApi\Model\Operation as ModelOperation;

#[ORM\Entity(repositoryClass: WorkEventDayRepository::class)]
#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    denormalizationContext: ['groups' => ['work_event_day:write']],
    normalizationContext: ['groups' => ['work_event_day:read']],
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            provider: MonthlyProvider::class
        ),
        new Get(
            security: "is_granted('VIEW', object)"
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: UserAssignmentProcessor::class,
        ),
        new Put(
            security: "is_granted('EDIT', object)"
        ),
        new Delete(
            security: "is_granted('EDIT', object)"
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/work_event_days/file_download',
            controller: WorkEventDayFileController::class,
            input: WorkEventDayDownloadFileInput::class,
            deserialize: false,
            openapi: new ModelOperation(
                security: [['bearerAuth' => []]],
                summary: 'Téléchargement du fichier PDF',
                responses: [
                    '200' => [
                        'description' => 'Fichier PDF',
                        'content' => [
                            'application/pdf' => [
                                'schema' => ['type' => 'string', 'format' => 'binary'],
                            ],
                        ],
                    ],
                ]
            )
        ),
    ],
)]
class WorkEventDay implements UserOwnerInterface, MonthlyProviderInterface
{
    private const string GROUP_WORK_EVENT_DAY_WRITE = 'work_event_day:write';

    public const string GROUP_WORK_EVENT_DAY_READ = 'work_event_day:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ, self::GROUP_WORK_EVENT_DAY_WRITE])]
    #[NotBlank]
    private string $title;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ, self::GROUP_WORK_EVENT_DAY_WRITE])]
    #[ApiFilter(SearchFilter::class, properties: ['startDate' => 'exact', 'date' => 'exact'])]
    private DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ, self::GROUP_WORK_EVENT_DAY_WRITE])]
    private DateTimeInterface $endDate;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    #[NotBlank]
    #[CssColor(message: 'Le code couleur {{ value }} ne correspond pas à un code valide')]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ, self::GROUP_WORK_EVENT_DAY_WRITE])]
    private string $color;

    #[ORM\ManyToOne(inversedBy: 'workEventDays')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ])]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups([self::GROUP_WORK_EVENT_DAY_READ, self::GROUP_WORK_EVENT_DAY_WRITE])]
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

    #[Groups([self::GROUP_WORK_EVENT_DAY_READ])]
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
