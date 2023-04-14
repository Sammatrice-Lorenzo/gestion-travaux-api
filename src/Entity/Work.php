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

#[ORM\Entity(repositoryClass: WorkRepository::class)]
#[ApiResource]
class Work
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end = null;

    #[ORM\Column(length: 255)]
    private ?string $progression = null;

    #[ORM\Column]
    private array $equipement = [];

    #[ORM\OneToMany(targetEntity: TypeOfWork::class, mappedBy: 'work')]
    private Collection $typeOfWorks;

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
