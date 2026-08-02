<?php

declare(strict_types=1);

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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Interface\MonthlyProviderInterface;
use Symfony\Component\HttpFoundation\File\File;
use App\Repository\SupplierReturnInvoiceFileRepository;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use App\Dto\SupplierReturnInvoiceCreationInput;
use App\Dto\SupplierReturnInvoiceUpdateInput;
use App\Dto\SupplierReturnInvoiceDownloadZipInput;
use App\Processor\SupplierReturnInvoiceProcessor;
use App\Controller\SupplierReturnInvoiceFileController;
use App\Controller\SupplierReturnInvoiceFileZipController;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\SupplierReturnInvoiceFileDownloadController;
use ApiPlatform\OpenApi\Model\Operation as ModelOperation;
use ApiPlatform\OpenApi\Model\RequestBody as ModelRequestBody;

#[ORM\Entity(repositoryClass: SupplierReturnInvoiceFileRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            provider: MonthlyProvider::class,
        ),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            controller: SupplierReturnInvoiceFileController::class,
            openapi: new ModelOperation(
                requestBody: new ModelRequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'date' => ['type' => 'string', 'format' => 'date'],
                                    'supplierId' => ['type' => 'integer'],
                                    'files' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'format' => 'binary'],
                                    ],
                                ],
                                'required' => ['date', 'files'],
                            ],
                        ],
                    ])
                ),
                security: [['bearerAuth' => []]]
            ),
            security: "is_granted('ROLE_USER')",
            input: SupplierReturnInvoiceCreationInput::class,
            deserialize: false
        ),
        new Delete(
            security: "is_granted('EDIT', object)",
        ),
        new Get(
            uriTemplate: '/supplier_return_invoice_files/{id}/download',
            controller: SupplierReturnInvoiceFileDownloadController::class,
            openapi: new ModelOperation(
                responses: [
                    '200' => [
                        'description' => 'Fichier PDF',
                        'content' => [
                            'application/pdf' => [
                                'schema' => ['type' => 'string', 'format' => 'binary'],
                            ],
                        ],
                    ],
                ],
                summary: 'Téléchargement du fichier PDF de retour',
                security: [['bearerAuth' => []]]
            ),
            security: "is_granted('ROLE_USER')",
            read: true,
            deserialize: false
        ),
        new Post(
            uriTemplate: '/supplier_return_invoice_files_download_zip',
            controller: SupplierReturnInvoiceFileZipController::class,
            openapi: new ModelOperation(
                responses: [
                    '200' => [
                        'description' => 'Fichier ZIP',
                        'content' => [
                            'application/zip' => [
                                'schema' => ['type' => 'string', 'format' => 'binary'],
                            ],
                        ],
                    ],
                ],
                summary: 'Télécharge un zip de factures de retour',
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
                security: [['bearerAuth' => []]]
            ),
            security: "is_granted('ROLE_USER')",
            input: SupplierReturnInvoiceDownloadZipInput::class,
            output: false,
            read: false,
            write: false
        ),
        new Put(
            security: "is_granted('EDIT', object)",
            input: SupplierReturnInvoiceUpdateInput::class,
            processor: SupplierReturnInvoiceProcessor::class
        ),
    ],
    normalizationContext: ['groups' => ['supplier_return_invoice_file:read']],
    denormalizationContext: ['groups' => ['supplier_return_invoice_file:write']],
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    )
)]
#[Vich\Uploadable]
class SupplierReturnInvoiceFile implements UserOwnerInterface, MonthlyProviderInterface
{
    public const string GROUP_READ = 'supplier_return_invoice_file:read';

    public const string GROUP_WRITE = 'supplier_return_invoice_file:write';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups([self::GROUP_READ, self::GROUP_WRITE])]
    private string $name;

    #[ORM\Column(length: 512)]
    #[Assert\NotBlank]
    #[Groups([self::GROUP_READ])]
    private ?string $path = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups([self::GROUP_READ])]
    #[ApiFilter(SearchFilter::class, properties: ['date' => 'exact'])]
    private DateTimeInterface $date;

    #[ORM\ManyToOne(inversedBy: 'supplierReturnInvoiceFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Assert\File(mimeTypes: ['application/pdf', 'application/x-pdf'])]
    #[Groups([self::GROUP_WRITE])]
    #[Vich\UploadableField(mapping: 'supplier_return_invoices', fileNameProperty: 'path')]
    private ?File $file = null;

    #[ORM\Column]
    #[Groups([self::GROUP_READ])]
    private float $creditAmount;

    #[ORM\ManyToOne(inversedBy: 'supplierReturnInvoiceFiles')]
    #[Groups([self::GROUP_READ])]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne]
    #[Groups([self::GROUP_READ])]
    private ?ProductInvoiceFile $linkedProductInvoice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): static
    {
        $this->date = $date;

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

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getCreditAmount(): float
    {
        return $this->creditAmount;
    }

    public function setCreditAmount(float $creditAmount): static
    {
        $this->creditAmount = $creditAmount;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getLinkedProductInvoice(): ?ProductInvoiceFile
    {
        return $this->linkedProductInvoice;
    }

    public function setLinkedProductInvoice(?ProductInvoiceFile $linkedProductInvoice): static
    {
        $this->linkedProductInvoice = $linkedProductInvoice;

        return $this;
    }
}
