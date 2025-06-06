<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\WorkImageRepository;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: WorkImageRepository::class)]
class WorkImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private string $imageName;

    #[ORM\Column]
    #[NotNull]
    private DateTimeImmutable $updateAt;

    #[ORM\ManyToOne(inversedBy: 'workImages')]
    #[ORM\JoinColumn(nullable: false)]
    #[NotNull]
    private Work $work;

    #[Vich\UploadableField(mapping: 'work_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getImageName(): string
    {
        return $this->imageName;
    }

    final public function setImageName(string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    final public function getUpdateAt(): DateTimeImmutable
    {
        return $this->updateAt;
    }

    final public function setUpdateAt(DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    final public function getWork(): ?Work
    {
        return $this->work;
    }

    final public function setWork(?Work $work): static
    {
        $this->work = $work;

        return $this;
    }

    final public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    final public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;

        return $this;
    }
}
