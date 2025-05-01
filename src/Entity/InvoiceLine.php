<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\InvoiceLineRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: InvoiceLineRepository::class)]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private ?string $localisation = null;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private string $description;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private string $unitPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    #[NotBlank]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private string $totalPriceLine;

    #[ORM\ManyToOne(inversedBy: 'invoiceLines')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private Invoice $invoice;

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    final public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    final public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    final public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    final public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    final public function getTotalPriceLine(): string
    {
        return $this->totalPriceLine;
    }

    final public function setTotalPriceLine(string $totalPriceLine): static
    {
        $this->totalPriceLine = $totalPriceLine;

        return $this;
    }

    final public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    final public function setInvoice(Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }
}
