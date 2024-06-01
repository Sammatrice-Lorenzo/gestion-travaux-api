<?php

namespace App\Controller;

use setasign\Fpdi\Fpdi;
use App\Service\ApiService;
use App\Service\InvoiceFileService;
use App\Service\InvoiceFormService;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class InvoiceController extends AbstractController
{
    #[Route(path: '/api/invoice-file', name: 'app_invoice', methods: ['POST'])]
    final public function generateInvoicePDF(
        Request $request,
        InvoiceFileService $invoiceFileService,
        InvoiceFormService $invoiceFormService,
        ClientRepository $clientRepository,
    ): BinaryFileResponse|JsonResponse
    {
        $jsonData = json_decode($request->getContent());
        $errorsRequest = $invoiceFormService->checkInvoiceData($jsonData);

        if ($errorsRequest) {
            return ApiService::getJsonResponseRequestParameters($errorsRequest);
        }

        $pdfExample = $this->getParameter('invoice_example_directory') . 'test.pdf';
        $pdf = new Fpdi();
        $invoiceFileService->setFpdi($pdf);

        $invoiceFileService->setupInvoiceParameterFile($pdfExample);
        $headers = ['LOCALISATION', 'DESCRIPTION DES PRESTATIONS', 'PRIX UNITAIRE', 'TOTAL DE LA LIGNE'];

        $client = $clientRepository->findOneBy(['id' => (int) $jsonData->idClient]);
        if (!$client) {
            throw new UserNotFoundException();
        }
        $invoiceFileService->generateInvoiceFile($client, $headers, $jsonData);

        $pdfFilePathWithAddedData = 'path_to_save_pdf_with_added_data.pdf';
        $pdf->Output($pdfFilePathWithAddedData, 'F');

        return $this->file($pdfFilePathWithAddedData, 'pdf_with_added_data.pdf');
    }
}
