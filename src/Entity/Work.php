<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\TypeOfWork;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\WorkRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Metadata\Get;
use App\Controller\WorkController;
use Symfony\Component\Serializer\Annotation\Groups;

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
            openapiContext: [
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => 'The user ID',
                    ]
                ]
            ]
        )
    ],
    normalizationContext: ['groups' => 'read:Work'],
)]
#[ApiResource]
class Work
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[Groups(['read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;
    #[Groups(['read:Work'])]

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start = null;
    #[Groups(['read:Work'])]

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end = null;
    #[Groups(['read:Work'])]

    #[ORM\Column(length: 255)]
    private ?string $progression = null;
    #[Groups(['read:Work'])]

    #[ORM\Column]
    private array $equipement = [];
    #[Groups(['read:Work'])]

    #[ORM\OneToMany(targetEntity: TypeOfWork::class, mappedBy: 'work')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $typeOfWorks = null;

    #[ORM\ManyToOne(inversedBy: 'works')]
    private ?User $user = null;

    public function __construct()
    {
        $this->typeOfWorks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getProgression(): ?string
    {
        return $this->progression;
    }

    public function setProgression(string $progression): self
    {
        $this->progression = $progression;

        return $this;
    }

    public function getEquipement(): array
    {
        return $this->equipement;
    }

    public function setEquipement(array $equipement): self
    {
        $this->equipement = $equipement;

        return $this;
    }

    /**
     * @return Collection<int, TypeOfWork>
     */
    public function getTypeOfWorks(): Collection
    {
        return $this->typeOfWorks;
    }

    public function addTypeOfWork(TypeOfWork $typeOfWork): self
    {
        if (!$this->typeOfWorks->contains($typeOfWork)) {
            $this->typeOfWorks->add($typeOfWork);
            $typeOfWork->setWork($this);
        }

        return $this;
    }

    public function removeTypeOfWork(TypeOfWork $typeOfWork): self
    {
        if ($this->typeOfWorks->removeElement($typeOfWork)) {
            // set the owning side to null (unless already changed)
            if ($typeOfWork->getWork() === $this) {
                $typeOfWork->setWork(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
