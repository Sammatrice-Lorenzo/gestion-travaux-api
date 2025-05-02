<?php

namespace App\Controller;

use App\Service\ZipService;
use App\Helper\DateFormatHelper;
use App\Entity\ProductInvoiceFile;
use App\Service\ProductInvoiceService;
use App\Dto\ProductInvoiceDownloadZipInput;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsController]
final class ProductInvoiceFileZipController extends AbstractController
{
    public function __construct(
        private ProductInvoiceService $productInvoiceService,
        private ParameterBagInterface $parameterBagInterface,
        private ProductInvoiceFileRepository $productInvoiceFileRepository
    ) {}

    public function __invoke(Request $request, SerializerInterface $serializerInterface): BinaryFileResponse|JsonResponse
    {
        $productInvoiceDownloadZipInput = $serializerInterface->deserialize(
            $request->getContent(),
            ProductInvoiceDownloadZipInput::class,
            'json'
        );

        /** @var ProductInvoiceFile[] */
        $productInvoices = $this->productInvoiceFileRepository->findBy(['id' => $productInvoiceDownloadZipInput->ids]);
        if (!$productInvoices) {
            return new JsonResponse([
                'error' => 'Aucun fichier a été trouvé !',
                'code' => JsonResponse::HTTP_BAD_REQUEST,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var ProductInvoiceFile $productInvoiceFile */
        $productInvoiceFile = end($productInvoices);

        $date = $productInvoiceFile->getDate()->format(DateFormatHelper::MONTH_FORMAT . '_' . DateFormatHelper::YEAR_FORMAT);
        $productInvoiceFiles = $this->productInvoiceService->getFiles($productInvoices);
        $nameZip = "Factures_{$date}";
        $zip = ZipService::getZipArchive(
            $productInvoiceFiles,
            $nameZip,
            $this->parameterBagInterface->get('products_invoice')
        );

        return $this->file($zip, "{$nameZip}.zip");
    }
}
