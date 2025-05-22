<?php

namespace App\Entity;

use ArrayObject;
use DateTimeInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use App\State\MonthlyProvider;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Interface\UserOwnerInterface;
use App\Dto\ProductInvoiceUpdateInput;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Dto\ProductInvoiceCreationInput;
use App\Processor\ProductInvoiceProcessor;
use App\Dto\ProductInvoiceDownloadZipInput;
use App\Interface\MonthlyProviderInterface;
use Symfony\Component\HttpFoundation\File\File;
use App\Controller\ProductInvoiceFileController;
use App\Repository\ProductInvoiceFileRepository;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Controller\ProductInvoiceFileZipController;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\ProductInvoiceFileDownloadController;
use ApiPlatform\OpenApi\Model\Operation as ModelOperation;
use ApiPlatform\OpenApi\Model\RequestBody as ModelRequestBody;

#[ORM\Entity(repositoryClass: ProductInvoiceFileRepository::class)]
#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    normalizationContext: ['groups' => ['product_invoice_file:read']],
    denormalizationContext: ['groups' => ['product_invoice_file:write']],
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            provider: MonthlyProvider::class,
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            controller: ProductInvoiceFileController::class,
            input: ProductInvoiceCreationInput::class,
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            openapi: new ModelOperation(
                security: [['bearerAuth' => []]],
                requestBody: new ModelRequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'date' => ['type' => 'string', 'format' => 'date'],
                                    'files' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'format' => 'binary'],
                                    ],
                                ],
                                'required' => ['date', 'files'],
                            ],
                        ],
                    ])
                )
            )
        ),
        new Delete(
            security: "is_granted('EDIT', object)",
        ),
        new Get(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/product_invoice_files/{id}/download',
            controller: ProductInvoiceFileDownloadController::class,
            read: true,
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
        new Post(
            security: "is_granted('ROLE_USER')",
            uriTemplate: '/product_invoice_files_download_zip',
            controller: ProductInvoiceFileZipController::class,
            input: ProductInvoiceDownloadZipInput::class,
            read: false,
            write: false,
            output: false,
            openapi: new ModelOperation(
                security: [['bearerAuth' => []]],
                summary: 'Télécharge un zip de factures',
                requestBody: new ModelRequestBody(
                    description: 'Liste des IDs',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'ids' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'integer'],
                                    ],
                                ],
                                'required' => ['ids'],
                            ],
                        ],
                    ])
                ),
                responses: [
                    '200' => [
                        'description' => 'Fichier ZIP',
                        'content' => [
                            'application/zip' => [
                                'schema' => ['type' => 'string', 'format' => 'binary'],
                            ],
                        ],
                    ],
                ]
            )
        ),
        new Put(
            security: "is_granted('EDIT', object)",
            input: ProductInvoiceUpdateInput::class,
            processor: ProductInvoiceProcessor::class
        ),
    ]
)]
#[Vich\Uploadable]
class ProductInvoiceFile implements UserOwnerInterface, MonthlyProviderInterface
{
    public const string GROUP_PRODUCT_INVOICE_FILE_READ = 'product_invoice_file:read';

    public const string GROUP_PRODUCT_INVOICE_FILE_WRITE = 'product_invoice_file:write';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_READ, self::GROUP_PRODUCT_INVOICE_FILE_WRITE])]
    private string $name;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_READ])]
    private ?string $path = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_READ])]
    #[ApiFilter(SearchFilter::class, properties: ['date' => 'exact'])]
    private DateTimeInterface $date;

    #[ORM\ManyToOne(inversedBy: 'productInvoiceFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Assert\File(mimeTypes: ['application/pdf', ['application/x-pdf']])]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_WRITE])]
    #[Vich\UploadableField(mapping: 'products_invoice', fileNameProperty: 'path')]
    private ?File $file = null;

    #[ORM\Column]
    #[Groups([self::GROUP_PRODUCT_INVOICE_FILE_READ])]
    private float $totalAmount;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    final public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Le path peut être null car avec Vich durant le delete il passe un null.
     */
    final public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    final public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    final public function setDate(DateTimeInterface $date): static
    {
        $this->date = $date;

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

    final public function getFile(): ?File
    {
        return $this->file;
    }

    final public function setFile(?File $file): self
    {
        $this->file = $file;

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
