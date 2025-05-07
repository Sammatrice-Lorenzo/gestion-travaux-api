<?php

namespace App\Controller;

use App\Entity\ProductInvoiceFile;
use App\Service\ProductInvoiceService;
use App\Dto\ProductInvoiceCreationInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductInvoiceFileController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private ProductInvoiceService $productInvoiceService,
    ) {}

    public function __invoke(Request $request, ValidatorInterface $validatorInterface): JsonResponse
    {
        $dto = new ProductInvoiceCreationInput();
        $dto->date = $request->request->get('date');
        $dto->files = $request->files->all('files');

        $errors = $validatorInterface->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse([
                'errors' => $errors->__tostring(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $productInvoiceFiles = $this->productInvoiceService->getProductInvoicesCreated($dto);

        $data = $this->serializer->serialize($productInvoiceFiles, 'json', [
            'groups' => ProductInvoiceFile::GROUP_PRODUCT_INVOICE_FILE_READ,
        ]);

        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }
}
