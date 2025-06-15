<?php

namespace App\Controller;

use App\Entity\WorkImage;
use App\Service\ApiErrorsService;
use App\Dto\WorkImageCreationInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\WorkImage\WorkImageCreationService;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkImageController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private WorkImageCreationService $workImageCreationService,
    ) {}

    public function __invoke(Request $request, ValidatorInterface $validatorInterface): JsonResponse
    {
        $dto = new WorkImageCreationInput();
        $dto->images = $request->files->all('images');
        $dto->workId = $request->request->get('workId');

        $errors = $validatorInterface->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse([
                'errors' => ApiErrorsService::getErrorsSeralizationInput($errors),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workImages = $this->workImageCreationService->getWorkImagesCreated($dto);
        $data = $this->serializer->serialize($workImages, 'json', [
            'groups' => WorkImage::GROUP_WORK_IMAGE_READ,
        ]);

        return new JsonResponse($data, JsonResponse::HTTP_CREATED, [], true);
    }
}
