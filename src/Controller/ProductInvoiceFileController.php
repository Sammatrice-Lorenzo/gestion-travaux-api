<?php

namespace App\Controller;

use DateTime;
use App\Entity\ProductInvoiceFile;
use App\Service\ProductInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use App\Formatter\ProductInvoiceFormatter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Checker\ProductInvoice\ProductInvoiceRequestChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductInvoiceFileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ProductInvoiceRequestChecker $productInvoiceRequestChecker,
        private ProductInvoiceService $productInvoiceService,
        private ParameterBagInterface $parameterBagInterface
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

        /** @var ProductInvoiceFileRepository $productsInvoicesRepository */
        $productsInvoicesRepository = $this->entityManager->getRepository(ProductInvoiceFile::class);
        $productInvoiceFiles = $productsInvoicesRepository->findByMonth($this->getUser(), $date);

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
}
