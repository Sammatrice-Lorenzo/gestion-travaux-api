<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\ProductInvoiceFile;
use App\Dto\ProductInvoiceCreationInput;
use Doctrine\ORM\EntityManagerInterface;
use App\Exceptions\UnsupportedImageFormatException;
use App\Service\ProductInvoiceFile\UploadedInvoiceFileConverter;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class ProductInvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private Security $security,
        private PdfExtractorService $pdfExtractorService,
        private UploadedInvoiceFileConverter $uploadedInvoiceFileConverter,
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
     * Les photos sont converties en PDF avant l'enregistrement ; aucune facture n'est créée si l'une est illisible.
     *
     * @param ProductInvoiceFile[] $productInvoiceFiles
     *
     * @throws UnsupportedImageFormatException
     */
    private function createProductInvoice(ProductInvoiceCreationInput $productInvoiceCreationInput, array &$productInvoiceFiles): void
    {
        $uploadedFiles = $productInvoiceCreationInput->files;
        $date = $productInvoiceCreationInput->date;

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        /** @var User $user */
        $user = $this->entityManagerInterface->getRepository(User::class)->find($currentUser->getId());

        $pdfFiles = [];

        try {
            foreach ($uploadedFiles as $uploadedFile) {
                $file = $this->uploadedInvoiceFileConverter->toPdf($uploadedFile);
                $pdfFiles[] = $file;

                // Une photo convertie ne contient pas de texte : montant à 0 et date du formulaire, à corriger à la main.
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
        } finally {
            foreach ($pdfFiles as $pdfFile) {
                $this->uploadedInvoiceFileConverter->removeTemporaryFile($pdfFile);
            }
        }
    }

    /**
     * @throws UnsupportedImageFormatException
     *
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
