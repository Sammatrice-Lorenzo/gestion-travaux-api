<?php

namespace App\MessageHandler;

use DateTime;
use App\Entity\Supplier;
use App\Entity\ProductInvoiceFile;
use App\Service\PdfExtractorService;
use App\Dto\ProductInvoiceResponseIA;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\ParseProductInvoiceFileMessage;
use Symfony\Component\HttpFoundation\File\File;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Service\ProductInvoiceFile\ProductInvoiceFileExtractionService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsMessageHandler]
final class ParseProductInvoiceFileMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private ProductInvoiceFileExtractionService $parserService,
        private ParameterBagInterface $parameterBagInterface,
        private PdfExtractorService $pdfExtractorService,
    ) {}

    public function __invoke(ParseProductInvoiceFileMessage $message): void
    {
        /**
         * @var ProductInvoiceFileRepository $productInvoiceFileRepository
         */
        $productInvoiceFileRepository = $this->entityManagerInterface->getRepository(ProductInvoiceFile::class);

        /** @var ?ProductInvoiceFile */
        $productInvoiceFile = $productInvoiceFileRepository->find($message->invoiceFileId);

        if (!$productInvoiceFile) {
            return;
        }

        $filePath = $this->parameterBagInterface->get('products_invoice') . $productInvoiceFile->getPath();
        $this->pdfExtractorService->setFile(new File($filePath));
        $extractText = $this->pdfExtractorService->getTextPdf();

        $data = $this->parserService->extractInvoiceData($extractText);
        $date = $data->invoice_date ? new DateTime($data->invoice_date) : $productInvoiceFile->getDate();
        $productInvoiceFile
            ->setDate($date)
            ->setTotalAmount((float) $data->total_amount ?? $productInvoiceFile->getTotalAmount())
        ;

        $this->handleSetSupplier($data, $productInvoiceFile);

        $this->entityManagerInterface->flush();
    }

    private function handleSetSupplier(ProductInvoiceResponseIA $productInvoiceResponseIA, ProductInvoiceFile $productInvoiceFile): void
    {
        $supplierResponse = $productInvoiceResponseIA->supplier;

        /** @var SupplierRepository $supplierRepository */
        $supplierRepository = $this->entityManagerInterface->getRepository(Supplier::class);

        $supplier = $supplierRepository->findByName($supplierResponse->name ?? '');

        if (!$supplier) {
            $supplier = (new Supplier())
                ->setName($supplierResponse->name)
                ->setAddress($supplierResponse->address)
                ->setCity($supplierResponse->city)
                ->setCountry($supplierResponse->country)
                ->setPhone($supplierResponse->phone)
                ->setVatNumber($supplierResponse->vatNumber)
                ->setUser($productInvoiceFile->getUser())
            ;
            $this->entityManagerInterface->persist($supplier);
        }

        $productInvoiceFile->setSupplier($supplier);
    }
}
