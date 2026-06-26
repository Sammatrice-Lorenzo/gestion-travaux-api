<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiErrorsService;
use App\Entity\SupplierReturnInvoiceFile;
use App\Service\SupplierReturnInvoiceService;
use App\Dto\SupplierReturnInvoiceCreationInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SupplierReturnInvoiceFileController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private SupplierReturnInvoiceService $supplierReturnInvoiceService,
    ) {}

    public function __invoke(Request $request, ValidatorInterface $validatorInterface): JsonResponse
    {
        $dto = new SupplierReturnInvoiceCreationInput();
        $dto->date = (string) $request->request->get('date');
        $dto->files = $request->files->all('files');
        $supplierId = $request->request->get('supplierId');
        $dto->supplierId = null !== $supplierId && '' !== $supplierId ? (int) $supplierId : null;

        $errors = $validatorInterface->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse([
                'errors' => ApiErrorsService::getErrorsSeralizationInput($errors),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invoices = $this->supplierReturnInvoiceService->getSupplierReturnInvoicesCreated($dto);

        $data = $this->serializer->serialize($invoices, 'json', [
            'groups' => SupplierReturnInvoiceFile::GROUP_READ,
        ]);

        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }
}
