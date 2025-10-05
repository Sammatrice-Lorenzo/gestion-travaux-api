<?php

namespace App\Entity;

use DateTimeImmutable;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Interface\UserOwnerInterface;
use App\Repository\SupplierRepository;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\Processor\UserAssignmentProcessor;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ApiResource(
    openapi: new Operation(
        security: [['bearerAuth' => []]],
    ),
    denormalizationContext: ['groups' => ['supplier:write']],
    normalizationContext: ['groups' => ['supplier:read']],
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
            security: "is_granted('EDIT', object)"
        ),
    ],
)]

class Supplier implements UserOwnerInterface
{
    private const string GROUP_SUPPLIER_WRITE = 'supplier:write';

    public const string GROUP_SUPPLIER_READ = 'supplier:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_SUPPLIER_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private string $name;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private string $address;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private string $city;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private string $country;

    #[ORM\Column(length: 255, nullable: true)]
    #[Regex(
        pattern: '/^(\+|00)[1-9]{1}[0-9]{0,2}[\s.-]?([0-9]{1,4}[\s.-]?){1,12}[0-9]{1,4}$/',
        message: 'Insérer un numéro de téléphone valide'
    )]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups([self::GROUP_SUPPLIER_READ, self::GROUP_SUPPLIER_WRITE])]
    private ?string $vatNumber = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'suppliers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([self::GROUP_SUPPLIER_READ])]
    private User $user;

    /**
     * @var Collection<int, ProductInvoiceFile>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: ProductInvoiceFile::class)]
    private Collection $productInvoiceFiles;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->productInvoiceFiles = new ArrayCollection();
    }

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

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new DateTimeImmutable();
        }
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

    /**
     * @return Collection<int, ProductInvoiceFile>
     */
    public function getProductInvoiceFiles(): Collection
    {
        return $this->productInvoiceFiles;
    }

    public function addProductInvoiceFile(ProductInvoiceFile $productInvoiceFile): static
    {
        if (!$this->productInvoiceFiles->contains($productInvoiceFile)) {
            $this->productInvoiceFiles->add($productInvoiceFile);
            $productInvoiceFile->setSupplier($this);
        }

        return $this;
    }

    public function removeProductInvoiceFile(ProductInvoiceFile $productInvoiceFile): static
    {
        if ($this->productInvoiceFiles->removeElement($productInvoiceFile)) {
            // set the owning side to null (unless already changed)
            if ($productInvoiceFile->getSupplier() === $this) {
                $productInvoiceFile->setSupplier(null);
            }
        }

        return $this;
    }
}
