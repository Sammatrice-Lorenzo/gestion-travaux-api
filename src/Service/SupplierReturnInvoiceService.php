<?php

declare(strict_types=1);

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\Supplier;
use App\Entity\SupplierReturnInvoiceFile;
use App\Dto\SupplierReturnInvoiceCreationInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class SupplierReturnInvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private Security $security,
        private PdfExtractorService $pdfExtractorService,
        private SupplierReturnInvoiceFileResolver $supplierReturnInvoiceFileResolver,
    ) {}

    private function getFormatDateByPdf(): string
    {
        $dateExtracted = $this->pdfExtractorService->extractDateFromPdf();
        if ('' === $dateExtracted) {
            return '';
        }

        $dateExtracted = array_reverse(explode('/', $dateExtracted));

        return str_replace('/', '-', implode('/', $dateExtracted));
    }

    /**
     * @param SupplierReturnInvoiceFile[] $supplierReturnInvoiceFiles
     */
    private function createSupplierReturnInvoices(
        SupplierReturnInvoiceCreationInput $input,
        array &$supplierReturnInvoiceFiles,
    ): void {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        /** @var User $user */
        $user = $this->entityManagerInterface->getRepository(User::class)->find($currentUser->getId());

        $supplier = null;
        if (null !== $input->supplierId) {
            $supplier = $this->entityManagerInterface->getRepository(Supplier::class)->find($input->supplierId);
        }

        foreach ($input->files as $file) {
            $this->pdfExtractorService->setFile($file);
            $creditAmount = $this->pdfExtractorService->extractTotalFromPdf();
            $dateExtracted = $this->getFormatDateByPdf();

            $returnInvoice = (new SupplierReturnInvoiceFile())
                ->setUser($user)
                ->setName($file->getClientOriginalName())
                ->setDate(new DateTime('' !== $dateExtracted ? $dateExtracted : $input->date))
                ->setFile($file)
                ->setCreditAmount($creditAmount)
                ->setSupplier($supplier)
            ;

            $this->entityManagerInterface->persist($returnInvoice);
            $supplierReturnInvoiceFiles[] = $returnInvoice;
        }

        $this->entityManagerInterface->flush();
    }

    /**
     * @return SupplierReturnInvoiceFile[]
     */
    public function getSupplierReturnInvoicesCreated(SupplierReturnInvoiceCreationInput $input): array
    {
        $supplierReturnInvoiceFiles = [];
        $this->createSupplierReturnInvoices($input, $supplierReturnInvoiceFiles);

        return $supplierReturnInvoiceFiles;
    }

    /**
     * @param SupplierReturnInvoiceFile[] $invoices
     *
     * @return string[]
     */
    public function getFiles(array $invoices): array
    {
        return array_map(
            fn (SupplierReturnInvoiceFile $invoice): string => $this->supplierReturnInvoiceFileResolver->resolveRelativePath($invoice),
            $invoices,
        );
    }
}
