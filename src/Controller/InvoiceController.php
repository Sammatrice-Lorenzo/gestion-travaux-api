<?php

namespace App\Controller;

use setasign\Fpdi\Fpdi;
use App\Service\InvoiceFileService;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceController extends AbstractController
{
    #[Route(path: '/api/invoice-file', name: 'app_invoice', methods: ['POST'])]
    public function generateInvoicePDF(
        InvoiceFileService $invoiceFileService,
        ClientRepository $clientRepository
    ): Response
    {
        $pdfExample = $this->getParameter('invoice_example_directory') . 'test.pdf';
        $pdf = new Fpdi();
        $invoiceFileService->setFpdi($pdf);

        $invoiceFileService->setupInvoiceParameterFile($pdfExample);

        $headers = ['LOCALISATION', 'DESCRIPTION DES PRESTATIONS', 'PRIX UNITAIRE', 'TOTAL DE LA LIGNE'];
        $invoiceData = [
            ['Item 1', 'Main d\'oeuvre et diverses fournitures', 'Ensemble', '10'],
            ['Item 2', 'Dépose des éléments sanitaires, lavabo, baignoires et wc, sans réemploi sauf le wc.', 'Ensemble', '20'],
            ['Item 3', 'Fourniture et pose de 2 prises et d\'1 double inter en applique sur goulotte. Pose d\'un interrupteur seul sans fourniture.', 'Ensemble', '50'],
            ['Item 3', 'Repose du wc, pose d\'un bac à douche avec platine easyfix double per, d\'un lavabo et d\'un sèche serviette', 'Ensemble', '50'],
        ];

        $nameInvoice = 'Travaux de préparation du chantier';
        $client = $clientRepository->findOneBy([], ['id' => 'ASC']);
        $invoiceFileService->generateInvoiceFile($client, $headers, $invoiceData, $nameInvoice);

        $pdfFilePathWithAddedData = 'path_to_save_pdf_with_added_data.pdf';
        $pdf->Output($pdfFilePathWithAddedData, 'F');

        return $this->file($pdfFilePathWithAddedData, 'pdf_with_added_data.pdf');
    }
}
