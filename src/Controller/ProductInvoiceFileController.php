<?php

namespace App\Controller;

use DateTime;
use App\Entity\ProductInvoiceFile;
use App\Service\ProductInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Checker\ProductInvoice\ProductInvoiceRequestChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductInvoiceFileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ProductInvoiceRequestChecker $productInvoiceRequestChecker,
        private ProductInvoiceService $productInvoiceService
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

        $data = $this->serializer->serialize($productInvoiceFiles, 'json', ['groups' => 'product_invoice_file:read']);

        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }
}
