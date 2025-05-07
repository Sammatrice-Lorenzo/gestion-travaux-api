<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\ProductInvoiceFile;
use App\Dto\ProductInvoiceCreationInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class ProductInvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private Security $security,
        private PdfExtractorService $pdfExtractorService
    ) {}

    private function getFormatDateByPdf(): string
    {
        $dateExtracted = $this->pdfExtractorService->extractDateFromPdf();
        $dateExtracted = array_reverse(explode('/', $dateExtracted));

        return str_replace('/', '-', implode('/', $dateExtracted));
    }

    /**
     * @param ProductInvoiceFile[] $productInvoiceFiles
     */
    private function createProductInvoice(ProductInvoiceCreationInput $productInvoiceCreationInput, array &$productInvoiceFiles): void
    {
        $uploadedFiles = $productInvoiceCreationInput->files;
        $date = $productInvoiceCreationInput->date;

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        /** @var User $user */
        $user = $this->entityManagerInterface->getRepository(User::class)->find($currentUser->getId());

        foreach ($uploadedFiles as $file) {
            $this->pdfExtractorService->setFile($file);
            $totalAmount = $this->pdfExtractorService->extractTotalFromPdf();
            $dateExtracted = $this->getFormatDateByPdf();

            $productInvoiceFile = (new ProductInvoiceFile())
                ->setUser($user)
                ->setName($file->getClientOriginalName())
                ->setDate(new DateTime('' !== $dateExtracted ? $dateExtracted : $date))
                ->setFile($file)
                ->setTotalAmount($totalAmount)
            ;
            $this->entityManagerInterface->persist($productInvoiceFile);

            $productInvoiceFiles[] = $productInvoiceFile;
        }

        $this->entityManagerInterface->flush();
    }

    /**
     * @return ProductInvoiceFile[]
     */
    public function getProductInvoicesCreated(ProductInvoiceCreationInput $productInvoiceCreationInput): array
    {
        $productInvoiceFiles = [];

        $this->createProductInvoice($productInvoiceCreationInput, $productInvoiceFiles);

        return $productInvoiceFiles;
    }

    /**
     * @param ProductInvoiceFile[] $productInvoice
     *
     * @return string[]
     */
    public function getFiles(array $productInvoice): array
    {
        return array_map(function (ProductInvoiceFile $productInvoiceFile): string {
            return $productInvoiceFile->getPath();
        }, $productInvoice);
    }
}
