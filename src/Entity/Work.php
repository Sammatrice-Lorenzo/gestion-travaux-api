<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Client;
use DateTimeInterface;
use App\Entity\TypeOfWork;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Enum\ProgressionEnum;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use App\Processor\WorkProcessor;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\WorkRepository;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Interface\UserOwnerInterface;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Processor\UserAssignmentProcessor;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkRepository::class)]
#[ApiResource(
    order: ['start' => 'DESC'],
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 30,
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    denormalizationContext: ['groups' => ['work:write']],
    normalizationContext: ['groups' => ['work:read']],
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')"
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
            security: "is_granted('EDIT', object)",
            processor: WorkProcessor::class
        ),
    ],
)]
class Work implements UserOwnerInterface
{
    private const string GROUP_WORK_WRITE = 'work:write';

    public const string GROUP_WORK_READ = 'work:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_WORK_READ])]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    #[NotBlank]
    private string $name;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    #[NotBlank]
    private string $city;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    private DateTimeInterface $start;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    private ?DateTimeInterface $end = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: [
        ProgressionEnum::NOT_STARTED->value,
        ProgressionEnum::IN_PROGRESS->value,
        ProgressionEnum::DONE->value,
    ])]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    private string $progression;

    /**
     * @var string[]
     */
    #[ORM\Column]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    private array $equipements = [];

    /**
     * @var Collection<int, TypeOfWork>
     */
    #[ORM\OneToMany(targetEntity: TypeOfWork::class, mappedBy: 'work', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $typeOfWorks;

    #[ORM\ManyToOne(inversedBy: 'works')]
    #[Groups([self::GROUP_WORK_READ])]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'works')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: true)]
    #[NotNull]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    private Client $client;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ApiProperty(readableLink: true)]
    private ?Invoice $invoice = null;

    #[ORM\Column]
    #[Groups([self::GROUP_WORK_READ, self::GROUP_WORK_WRITE])]
    #[Assert\Range(
        min: 0,
        notInRangeMessage: 'Le minimum autorisé est de {{ min }}',
    )]
    private float $totalAmount = 0.0;

    public function __construct()
    {
        $this->typeOfWorks = new ArrayCollection();
    }

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    final public function getCity(): string
    {
        return $this->city;
    }

    final public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    final public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    final public function setStart(DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    final public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    final public function setEnd(DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    final public function getProgression(): string
    {
        return $this->progression;
    }

    final public function setProgression(string $progression): self
    {
        $this->progression = $progression;

        return $this;
    }

    /**
     * @return string[]
     */
    final public function getEquipements(): array
    {
        return $this->equipements;
    }

    /**
     * @param string[] $equipements
     *
     * @return Work
     */
    final public function setEquipements(array $equipements): self
    {
        $this->equipements = $equipements;

        return $this;
    }

    /**
     * @return Collection<int, TypeOfWork>
     */
    final public function getTypeOfWorks(): Collection
    {
        return $this->typeOfWorks;
    }

    final public function addTypeOfWork(TypeOfWork $typeOfWork): self
    {
        if (!$this->typeOfWorks->contains($typeOfWork)) {
            $this->typeOfWorks->add($typeOfWork);
            $typeOfWork->setWork($this);
        }

        return $this;
    }

    final public function removeTypeOfWork(TypeOfWork $typeOfWork): self
    {
        if ($this->typeOfWorks->removeElement($typeOfWork) && $typeOfWork->getWork() === $this) {
            // set the owning side to null (unless already changed)
            $typeOfWork->setWork(null);
        }

        return $this;
    }

    final public function getUser(): User
    {
        return $this->user;
    }

    final public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    final public function getClient(): Client
    {
        return $this->client;
    }

    final public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    #[Groups([self::GROUP_WORK_READ])]
    final public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    final public function setInvoice(?Invoice $invoice): static
    {
        if ($invoice?->getWork() !== $this) {
            $invoice?->setWork($this);
        }

        $this->invoice = $invoice;

        return $this;
    }

    final public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    final public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }
}
