<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SupplierRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Time;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ApiResource]
class Supplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private string $name;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private string $address;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private string $city;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    private string $country;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column]
    #[Time]
    private DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'suppliers')]
    #[ORM\JoinColumn(nullable: false)]
    #[NotNull]
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

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
