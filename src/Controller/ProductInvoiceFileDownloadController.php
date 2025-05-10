<?php

namespace App\Controller;

use App\Entity\ProductInvoiceFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ProductInvoiceFileDownloadController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $parameterBagInterface,
    ) {}

    public function __invoke(ProductInvoiceFile $productInvoiceFile): BinaryFileResponse
    {
        /** @var string $path */
        $path = $this->parameterBagInterface->get('products_invoice') . $productInvoiceFile->getPath();

        return $this->file($path, $productInvoiceFile->getName());
    }
}
