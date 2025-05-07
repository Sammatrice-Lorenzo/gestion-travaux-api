<?php

namespace App\Processor;

use DateTime;
use ApiPlatform\Metadata\Put;
use App\Entity\ProductInvoiceFile;
use ApiPlatform\Metadata\Operation;
use App\Dto\ProductInvoiceUpdateInput;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\State\ProcessorInterface;

/**
 * @implements ProcessorInterface<ProductInvoiceUpdateInput, ProductInvoiceFile|void>
 */
final class ProductInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManagerInterface,
    ) {}

    /**
     * @param ProductInvoiceUpdateInput $data
     *
     * @return ProductInvoiceFile
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductInvoiceFile
    {
        $id = $uriVariables['id'];
        $invoice = $this->entityManagerInterface->getRepository(ProductInvoiceFile::class)->find($id);
        if ($operation instanceof Put) {
            $invoice
                ->setDate(new DateTime($data->date))
                ->setName($data->name)
                ->setTotalAmount((float) $data->totalAmount)
            ;
        }

        $this->entityManagerInterface->flush();

        return $invoice;
    }
}
