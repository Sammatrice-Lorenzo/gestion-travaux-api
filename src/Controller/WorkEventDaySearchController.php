<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Service\ApiErrorsService;
use App\Entity\WorkEventDay;
use App\Dto\WorkEventDaySearchInput;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\WorkEventDayRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkEventDaySearchController extends AbstractController
{
    public function __construct(
        private readonly WorkEventDayRepository $workEventDayRepository,
    ) {}

    public function __invoke(
        Request $request,
        SerializerInterface $serializerInterface,
        ValidatorInterface $validatorInterface,
    ): JsonResponse {
        /** @var WorkEventDaySearchInput $workEventDaySearchInput */
        $workEventDaySearchInput = $serializerInterface->deserialize(
            $request->getContent(),
            WorkEventDaySearchInput::class,
            'json'
        );

        $errors = $validatorInterface->validate($workEventDaySearchInput);
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
                $workEventDaySearchInput->client,
                $workEventDaySearchInput->search,
                new DateTime($workEventDaySearchInput->startDate),
                new DateTime($workEventDaySearchInput->endDate),
            );
        } catch (DbalException) {
            return ApiErrorsService::getHydraDescriptionResponse('Expression régulière invalide.');
        }

        $data = $serializerInterface->serialize($workEventDays, 'json', [
            'groups' => WorkEventDay::GROUP_WORK_EVENT_DAY_READ,
        ]);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
