<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SupplierReturnInvoiceFile;
use App\Service\SupplierReturnInvoiceFileResolver;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SupplierReturnInvoiceFileDownloadController extends AbstractController
{
    public function __construct(
        private SupplierReturnInvoiceFileResolver $supplierReturnInvoiceFileResolver,
    ) {}

    public function __invoke(SupplierReturnInvoiceFile $supplierReturnInvoiceFile): BinaryFileResponse
    {
        $path = $this->supplierReturnInvoiceFileResolver->resolveAbsolutePath($supplierReturnInvoiceFile);

        return $this->file($path, $supplierReturnInvoiceFile->getName());
    }
}
