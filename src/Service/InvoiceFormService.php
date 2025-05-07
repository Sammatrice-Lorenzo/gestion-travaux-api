<?php

namespace App\Service;

use stdClass;
use App\Entity\Work;
use App\Entity\Invoice;
use App\Entity\InvoiceLine;
use Doctrine\ORM\EntityManagerInterface;

final readonly class InvoiceFormService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
    ) {}

    /**
     * @param stdClass $invoiceData
     *
     * @return string[]
     */
    public function checkInvoiceData(stdClass $invoiceData): array
    {
        /** @var string[] $errorMessage */
        $errorMessage = [];
        
        /** @var string[] $properties */
        $properties = ['nameInvoice', 'invoiceLines', 'idClient', 'idWork'];

        foreach ($properties as $property) {
            if (!property_exists($invoiceData, $property)) {
                $errorMessage[] = "Le champ {$property} est obligatoire";
            }
        }

        return $errorMessage;
    }

    public function updateForm(stdClass $invoiceData): void
    {
        /** @var Work $work */
        $work = $this->entityManagerInterface->getRepository(Work::class)->find($invoiceData->idWork);

        $invoice = $work->getInvoice();
        if ($invoice) {
            $invoice->setTitle($invoiceData->nameInvoice);
            $this->deleteInvoiceLine($invoice);
            $this->createInvoiceLine($invoice, $invoiceData->invoiceLines);
        } else {
            $this->createInvoice($work, $invoiceData->nameInvoice, $invoiceData->invoiceLines);
        }
    }

    /**
     * @param Work $work
     * @param string $title
     * @param array<int, array<int, string>> $invoiceLines
     */
    private function createInvoice(Work $work, string $title, array $invoiceLines): void
    {
        $invoice = (new Invoice())
            ->setTitle($title)
            ->setWork($work)
        ;

        $work->setInvoice($invoice);
        $this->entityManagerInterface->persist($invoice);
        $this->createInvoiceLine($invoice, $invoiceLines);
        $this->entityManagerInterface->flush();
    }

    /**
     * @param Invoice $invoice
     * @param array<int, array<int, string>> $invoiceLines
     */
    private function createInvoiceLine(Invoice $invoice, array $invoiceLines): void
    {
        foreach ($invoiceLines as $line) {
            $newInvoiceLine = (new InvoiceLine())
                ->setInvoice($invoice)
                ->setLocalisation($line[0])
                ->setDescription($line[1])
                ->setUnitPrice($line[2])
                ->setTotalPriceLine($line[3])
            ;

            $this->entityManagerInterface->persist($newInvoiceLine);
        }

        $this->entityManagerInterface->flush();
    }

    private function deleteInvoiceLine(Invoice $invoice): void
    {
        foreach ($invoice->getInvoiceLines() as $line) {
            $this->entityManagerInterface->remove($line);
        }

        $this->entityManagerInterface->flush();
    }
}
