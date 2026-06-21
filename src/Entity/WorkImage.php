<?php

namespace App\Entity;

use ArrayObject;
use DateTimeImmutable;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use App\Dto\WorkImageCreationInput;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\WorkImageController;
use App\Repository\WorkImageRepository;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\File\File;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\Validator\Constraints\NotNull;
use ApiPlatform\OpenApi\Model\Operation as ModelOperation;
use ApiPlatform\OpenApi\Model\RequestBody as ModelRequestBody;

#[ORM\Entity(repositoryClass: WorkImageRepository::class)]
#[ApiResource(operations: [
    new Post(
        inputFormats: ['multipart' => ['multipart/form-data']],
        controller: WorkImageController::class,
        openapi: new ModelOperation(
            requestBody: new ModelRequestBody(
                content: new ArrayObject([
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'workId' => ['type' => 'integer', 'format' => 'integer'],
                                'files' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string', 'format' => 'binary'],
                                ],
                            ],
                            'required' => ['workId', 'files'],
                        ],
                    ],
                ])
            ),
            security: [['bearerAuth' => []]]
        ),
        security: "is_granted('ROLE_USER')",
        input: WorkImageCreationInput::class,
        deserialize: false
    ),
    new GetCollection(
        security: "is_granted('ROLE_USER')",
    ),
    new Delete(
        security: "is_granted('EDIT_WORK_IMAGE', object)",
    ),
], normalizationContext: ['groups' => ['work_image:read']], denormalizationContext: ['groups' => ['work_image:write']], openapi: new Operation(
    security: [['bearerAuth' => []]],
))]
#[Vich\Uploadable]
class WorkImage
{
    private const string GROUP_WORK_IMAGE_WRITE = 'work_image:write';

    public const string GROUP_WORK_IMAGE_READ = 'work_image:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_WORK_IMAGE_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups([self::GROUP_WORK_IMAGE_WRITE, self::GROUP_WORK_IMAGE_READ])]
    private ?string $imageName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[NotNull]
    #[Groups([self::GROUP_WORK_IMAGE_WRITE, self::GROUP_WORK_IMAGE_READ])]
    private DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'workImages')]
    #[ORM\JoinColumn(nullable: false)]
    #[NotNull]
    #[ApiFilter(SearchFilter::class, properties: ['work.id' => 'exact'])]
    private Work $work;

    #[Vich\UploadableField(mapping: 'work_images', fileNameProperty: 'imageName')]
    #[Groups([self::GROUP_WORK_IMAGE_WRITE])]
    private ?File $imageFile = null;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getImageName(): ?string
    {
        return $this->imageName;
    }

    final public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    final public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    final public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    final public function getWork(): \App\Entity\Work
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
