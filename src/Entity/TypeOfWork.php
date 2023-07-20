<?php

namespace App\Entity;

use App\Entity\Work;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\TypeOfWorkRepository;

#[ORM\Entity(repositoryClass: TypeOfWorkRepository::class)]
#[ApiResource]
class TypeOfWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private array $equipements = [];

    #[ORM\ManyToOne(targetEntity: Work::class, inversedBy: 'typeOfWorks')]
    private ?Work $work = null;

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

    public function getEquipements(): array
    {
        return $this->equipements;
    }

    public function setEquipements(array $equipements): self
    {
        $this->equipements = $equipements;

        return $this;
    }

    public function getWork(): ?Work
    {
        return $this->work;
    }

    public function setWork(?Work $work): self
    {
        $this->work = $work;

        return $this;
    }
}
