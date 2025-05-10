<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiResource]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private string $title;

    /**
     * @var Collection<int, InvoiceLine>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceLine::class, orphanRemoval: true)]
    #[Groups(['read:Invoice', Work::GROUP_WORK_READ])]
    private Collection $invoiceLines;

    #[ORM\OneToOne(inversedBy: 'invoice')]
    #[ORM\JoinColumn(nullable: false)]
    private Work $work;

    public function __construct()
    {
        $this->invoiceLines = new ArrayCollection();
    }

    final public function getId(): ?int
    {
        return $this->id;
    }

    final public function getTitle(): string
    {
        return $this->title;
    }

    final public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceLine>
     */
    final public function getInvoiceLines(): Collection
    {
        return $this->invoiceLines;
    }

    final public function addInvoiceLine(InvoiceLine $invoiceLine): static
    {
        if (!$this->invoiceLines->contains($invoiceLine)) {
            $this->invoiceLines->add($invoiceLine);
            $invoiceLine->setInvoice($this);
        }

        return $this;
    }

    final public function getWork(): Work
    {
        return $this->work;
    }

    final public function setWork(Work $work): static
    {
        $this->work = $work;

        return $this;
    }
}
