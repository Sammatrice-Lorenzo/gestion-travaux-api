<?php

declare(strict_types=1);

namespace App\Processor;

use DateTime;
use App\Entity\Supplier;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Operation;
use App\Entity\ProductInvoiceFile;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupplierReturnInvoiceFile;
use App\Dto\SupplierReturnInvoiceUpdateInput;

/**
 * @implements ProcessorInterface<SupplierReturnInvoiceUpdateInput, SupplierReturnInvoiceFile|void>
 */
final class SupplierReturnInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManagerInterface,
    ) {}

    /**
     * @param SupplierReturnInvoiceUpdateInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SupplierReturnInvoiceFile
    {
        $id = $uriVariables['id'];
        /** @var SupplierReturnInvoiceFile $invoice */
        $invoice = $this->entityManagerInterface->getRepository(SupplierReturnInvoiceFile::class)->find($id);

        if ($operation instanceof Put) {
            $supplier = $data->supplierId
                ? $this->entityManagerInterface->getRepository(Supplier::class)->find($data->supplierId)
                : null;

            $linkedProductInvoice = $data->linkedProductInvoiceId
                ? $this->entityManagerInterface->getRepository(ProductInvoiceFile::class)->find($data->linkedProductInvoiceId)
                : null;

            $invoice
                ->setDate(new DateTime($data->date))
                ->setName($data->name)
                ->setCreditAmount((float) $data->creditAmount)
                ->setSupplier($supplier)
                ->setLinkedProductInvoice($linkedProductInvoice)
            ;
        }

        $this->entityManagerInterface->flush();

        return $invoice;
    }
}
