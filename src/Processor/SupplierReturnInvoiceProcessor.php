<?php

declare(strict_types=1);

namespace App\Processor;

use DateTime;
use App\Entity\Supplier;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupplierReturnInvoiceFile;
use App\Dto\SupplierReturnInvoiceUpdateInput;
use App\Service\SupplierReturnInvoiceLinkValidator;

/**
 * @implements ProcessorInterface<SupplierReturnInvoiceUpdateInput, SupplierReturnInvoiceFile|void>
 */
final class SupplierReturnInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManagerInterface,
        private readonly SupplierReturnInvoiceLinkValidator $linkValidator,
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

            $linkedProductInvoice = $this->linkValidator->resolve(
                $invoice,
                $data->linkedProductInvoiceId,
                $supplier,
            );

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
