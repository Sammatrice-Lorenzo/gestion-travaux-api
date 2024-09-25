<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Client;
use DateTimeInterface;
use App\Entity\TypeOfWork;
use ApiPlatform\Metadata\Get;
use App\Enum\ProgressionEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\WorkController;
use App\Repository\WorkRepository;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkRepository::class)]
#[ApiResource(
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(
            name: 'getWorksByUser',
            uriTemplate: '/worksByUser/{id}',
            controller: WorkController::class,
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
    normalizationContext: ['groups' => ['read:Work', 'read:Client', 'read:Invoice']],
)]
#[ApiResource]
class Work
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Work'])]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    #[Groups(['read:Work'])]
    private string $name;
    
    #[ORM\Column(length: 255)]
    #[Groups(['read:Work'])]
    private string $city;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read:Work'])]
    private DateTimeInterface $start;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['read:Work'])]
    private ?DateTimeInterface $end = null;
    
    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: [
        ProgressionEnum::NOT_STARTED->value,
        ProgressionEnum::IN_PROGRESS->value,
        ProgressionEnum::DONE->value
        ])]
    #[Groups(['read:Work'])]
    private ?string $progression = null;
    /**
     * @var string[]
     */
    #[ORM\Column]
    #[Groups(['read:Work'])]
    private array $equipements = [];

    /**
     * @var Collection<int, TypeOfWork>
     */
    #[ORM\OneToMany(targetEntity: TypeOfWork::class, mappedBy: 'work', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $typeOfWorks = null;

    #[ORM\ManyToOne(inversedBy: 'works')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'works')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(readableLink: true)]
    private Client $client;
    
    #[ORM\OneToOne(mappedBy: 'work', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(readableLink: true)]
    private ?Invoice $invoice = null;

    #[ORM\Column]
    #[Groups(['read:Work'])]
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

    final public function getName(): ?string
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

    final public function getEquipements(): array
    {
        return $this->equipements;
    }

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

    final public function getUser(): ?User
    {
        return $this->user;
    }

    final public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['read:Work'])]
    final public function getClient(): ?Client
    {
        return $this->client;
    }

    final public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    #[Groups(['read:Work'])]
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

    #[Groups(['read:Work'])]
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
