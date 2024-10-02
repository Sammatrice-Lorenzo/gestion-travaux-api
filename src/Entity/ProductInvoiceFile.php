<?php

namespace App\Entity;

use ArrayObject;
use DateTimeInterface;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Dto\ProductInvoiceUpdateInput;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\HttpFoundation\File\File;
use App\Controller\ProductInvoiceFileController;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\OpenApi\Model\Operation as ModelOperation;
use ApiPlatform\OpenApi\Model\RequestBody as ModelRequestBody;

#[ORM\Entity(repositoryClass: ProductInvoiceFileRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['product_invoice_file:read']],
    denormalizationContext: ['groups' => ['product_invoice_file:write']],
    types: ['https://schema.org/MediaObject'],
    outputFormats: ['jsonld' => ['application/ld+json']],
    operations: [
        new GetCollection(
            description: 'Récupère les factures des produits selon le moi en paramètre',
            uriTemplate: '/api/product_invoice/month',
            controller: ProductInvoiceFileController::class,
            read: true,
            openapi: new ModelOperation(
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            controller: ProductInvoiceFileController::class,
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            openapi: new ModelOperation(
                requestBody: new ModelRequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            )
        ),
        new Delete(
            uriTemplate: '/api/product_invoice_delete/{id}',
            controller: ProductInvoiceFileController::class,
            openapi: new ModelOperation(
                summary: 'Supprime un fichier de facture',
                description: 'Permet de supprimer un fichier de facture spécifique.',
                security: [['bearerAuth' => []]]
            )
        ),
        new Post(
            controller: ProductInvoiceFileController::class,
            uriTemplate: '/api/product_invoice_download/{id}',
            input: [],
            openapi: new ModelOperation(
                summary: 'Télécharge un fichier de facture',
                description: 'Permet de télécharger un fichier de facture spécifique.',
                security: [['bearerAuth' => []]]
            )
        ),
        new Put(
            controller: ProductInvoiceFileController::class,
            uriTemplate: '/api/product_invoice_update/{id}',
            input: ProductInvoiceUpdateInput::class,
            openapi: new ModelOperation(
                summary: 'Mise à jour de certains champs',
                description: 'Permet de mettre à jour la date et le montant total de la facture.',
                security: [['bearerAuth' => []]]
            )
        )
    ]
)]
#[Vich\Uploadable]
class ProductInvoiceFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product_invoice_file:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product_invoice_file:read', 'product_invoice_file:write'])]
    private string $name;
    
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product_invoice_file:read'])]
    private ?string $path = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['product_invoice_file:read'])]
    private DateTimeInterface $date;

    #[ORM\ManyToOne(inversedBy: 'productInvoiceFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Assert\File(mimeTypes: ['application/pdf', ['application/x-pdf']])]
    #[Groups(['product_invoice_file:write'])]
    #[Vich\UploadableField(mapping: "products_invoice", fileNameProperty: "path")]
    private ?File $file = null;

    #[ORM\Column]
    #[Groups(['product_invoice_file:read'])]
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
     * Le path peut être null car avec Vich durant le delete il passe un null
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
