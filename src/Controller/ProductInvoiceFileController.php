<?php

namespace App\Controller;

use DateTime;
use App\Service\ZipService;
use App\Helper\DateFormatHelper;
use App\Entity\ProductInvoiceFile;
use App\Service\ProductInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use App\Formatter\ProductInvoiceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Checker\ProductInvoice\ProductInvoiceRequestChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ProductInvoiceFileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ProductInvoiceRequestChecker $productInvoiceRequestChecker,
        private ProductInvoiceService $productInvoiceService,
        private ParameterBagInterface $parameterBagInterface,
        private ProductInvoiceFileRepository $productInvoiceFileRepository
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $errorsMessage = $this->productInvoiceRequestChecker->checkBodyAPI($request);
        if (!empty(json_decode($errorsMessage->getContent()))) {
            return $errorsMessage;
        }

        $productInvoiceFiles = $this->productInvoiceService->getProductInvoicesCreated($request);

        $data = $this->serializer->serialize($productInvoiceFiles, 'json', ['groups' => 'product_invoice_file:read']);

        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: '/api/product_invoice/month', name: 'app_product_invoice_month', methods: ['GET'])]
    public function getProductsInvoices(Request $request): JsonResponse
    {
        $errorsMessage = $this->productInvoiceRequestChecker->handleApiGetError($request);
        if (!empty(json_decode($errorsMessage->getContent()))) {
            return $errorsMessage;
        }

        $date = new DateTime($request->query->get('date'));
        $productInvoiceFiles = $this->productInvoiceFileRepository->findByMonth($this->getUser(), $date);

        $response = ProductInvoiceFormatter::getResponseProductInvoice($productInvoiceFiles);
        $data = $this->serializer->serialize($response, 'jsonld', ['groups' => 'product_invoice_file:read']);
    
        return new JsonResponse($data, JsonResponse::HTTP_OK, [
            'Content-Type' => 'application/ld+json'
        ], true);
    }

    #[Route(path: '/api/product_invoice_delete/{id}', name: 'app_product_invoice_month_delete', methods: ['DELETE'])]
    public function deleteProductInvoice(Request $request, ProductInvoiceFile $productInvoiceFile): JsonResponse
    {
        if ($this->productInvoiceRequestChecker->handleErrorToken($request)) {
            return $this->productInvoiceRequestChecker->handleErrorToken($request);
        }

        $this->entityManager->remove($productInvoiceFile);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true], JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/product_invoice_download/{id}', name: 'app_product_invoice_month_download', methods: ['POST'])]
    public function downloadProductInvoice(Request $request, ProductInvoiceFile $productInvoiceFile): JsonResponse|BinaryFileResponse
    {
        if ($this->productInvoiceRequestChecker->handleErrorToken($request)) {
            return $this->productInvoiceRequestChecker->handleErrorToken($request);
        }

        /** @var string $path */
        $path = $this->parameterBagInterface->get('products_invoice') . $productInvoiceFile->getPath();

        return $this->file($path, $productInvoiceFile->getName());
    }

    #[Route(path: '/api/product_invoice_download_zip', name: 'app_product_invoice_download_zip', methods: ['POST'])]
    public function downloadZIPProductInvoice(Request $request): JsonResponse|BinaryFileResponse
    {
        if ($this->productInvoiceRequestChecker->handleErrorIds($request)) {
            return $this->productInvoiceRequestChecker->handleErrorIds($request);
        }

        /** @var string[] */
        $productInvoiceIds = json_decode($request->getContent())->ids;

        /** @var ProductInvoiceFile[] */
        $productInvoices = $this->productInvoiceFileRepository->findBy(['id' => $productInvoiceIds]);
        
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

    #[Route(path: '/api/product_invoice_update/{id}', name: 'app_product_invoice_update', methods: ['PUT'])]
    public function update(Request $request, ProductInvoiceFile $productInvoiceFile): JsonResponse
    {
        if ($this->productInvoiceRequestChecker->handleErrorBodyForPut($request)) {
            return $this->productInvoiceRequestChecker->handleErrorBodyForPut($request);
        }

        $data = json_decode($request->getContent());

        $productInvoiceFile
            ->setName($data->name)
            ->setDate(new DateTime($data->date))
            ->setTotalAmount((float) $data->totalAmount)
        ;

        $this->entityManager->flush();
        
        return new JsonResponse(['success' => true], status: JsonResponse::HTTP_OK);
    }
}
