<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ZipService;
use App\Helper\DateFormatHelper;
use App\Entity\SupplierReturnInvoiceFile;
use App\Service\SupplierReturnInvoiceService;
use App\Dto\SupplierReturnInvoiceDownloadZipInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Repository\SupplierReturnInvoiceFileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class SupplierReturnInvoiceFileZipController extends AbstractController
{
    public function __construct(
        private SupplierReturnInvoiceService $supplierReturnInvoiceService,
        private ParameterBagInterface $parameterBagInterface,
        private SupplierReturnInvoiceFileRepository $supplierReturnInvoiceFileRepository,
    ) {}

    public function __invoke(Request $request, SerializerInterface $serializerInterface): BinaryFileResponse|JsonResponse
    {
        /** @var SupplierReturnInvoiceDownloadZipInput $input */
        $input = $serializerInterface->deserialize(
            $request->getContent(),
            SupplierReturnInvoiceDownloadZipInput::class,
            'json'
        );

        /** @var SupplierReturnInvoiceFile[] $invoices */
        $invoices = $this->supplierReturnInvoiceFileRepository->findBy(['id' => $input->ids]);
        if (!$invoices) {
            return new JsonResponse([
                'error' => 'Aucun fichier a été trouvé !',
                'code' => JsonResponse::HTTP_BAD_REQUEST,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var SupplierReturnInvoiceFile $lastInvoice */
        $lastInvoice = end($invoices);

        $date = $lastInvoice->getDate()->format(DateFormatHelper::MONTH_FORMAT . '_' . DateFormatHelper::YEAR_FORMAT);
        $files = $this->supplierReturnInvoiceService->getFiles($invoices);
        $nameZip = "Retours_fournisseur_{$date}";
        $zip = ZipService::getZipArchive(
            $files,
            $nameZip,
            $this->parameterBagInterface->get('supplier_return_invoices')
        );

        return $this->file($zip, "{$nameZip}.zip");
    }
}
