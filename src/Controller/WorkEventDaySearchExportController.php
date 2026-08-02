<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Service\ApiErrorsService;
use Doctrine\DBAL\Exception as DbalException;
use App\Dto\WorkEventDaySearchExportInput;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\WorkEventDayRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\WorkEventDaySearchExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkEventDaySearchExportController extends AbstractController
{
    public function __construct(
        private readonly WorkEventDayRepository $workEventDayRepository,
        private readonly WorkEventDaySearchExportService $workEventDaySearchExportService,
    ) {}

    public function __invoke(
        Request $request,
        SerializerInterface $serializerInterface,
        ValidatorInterface $validatorInterface,
    ): BinaryFileResponse|JsonResponse {
        /** @var WorkEventDaySearchExportInput $workEventDaySearchExportInput */
        $workEventDaySearchExportInput = $serializerInterface->deserialize(
            $request->getContent(),
            WorkEventDaySearchExportInput::class,
            'json'
        );

        $errors = $validatorInterface->validate($workEventDaySearchExportInput);
        if (count($errors) > 0) {
            return ApiErrorsService::getHydraDescriptionResponse(
                implode(', ', ApiErrorsService::getErrorsSeralizationInput($errors)),
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $workEventDays = $this->workEventDayRepository->search(
                $user,
                $workEventDaySearchExportInput->client,
                $workEventDaySearchExportInput->search,
                new DateTime($workEventDaySearchExportInput->startDate),
                new DateTime($workEventDaySearchExportInput->endDate),
            );
        } catch (DbalException) {
            return ApiErrorsService::getHydraDescriptionResponse('Expression régulière invalide.');
        }

        $filePath = $this->workEventDaySearchExportService->export($workEventDays, $workEventDaySearchExportInput->format);
        $fileName = "prestations_recherche.{$workEventDaySearchExportInput->format}";

        $response = $this->file($filePath, $fileName);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
