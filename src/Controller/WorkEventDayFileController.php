<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use setasign\Fpdi\Fpdi;
use App\Service\ApiService;
use App\Service\WorkEventDayFileService;
use App\Dto\WorkEventDayDownloadFileInput;
use App\Repository\WorkEventDayRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkEventDayFileController extends AbstractController
{
    public function __construct(
        private readonly WorkEventDayRepository $workEventDayRepository,
        private readonly ApiService $apiService
    ) {}

    public function __invoke(
        Request $request,
        WorkEventDayFileService $workEventDayFileService,
        SerializerInterface $serializerInterface
    ): JsonResponse|BinaryFileResponse {
        $workEventDayFileInput = $serializerInterface->deserialize(
            $request->getContent(),
            WorkEventDayDownloadFileInput::class,
            'json'
        );

        $pdf = new Fpdi();

        /** @var User $user */
        $user = $this->getUser();
        $date = new DateTime($workEventDayFileInput->date);
        $workEventDays = $this->workEventDayRepository->findByMonth($user, $date);

        $header = ['Date', 'Prestation', 'Début', 'Fin', 'Client'];

        $workEventDayFileService->setFpdi($pdf);
        $workEventDayFileService->generateFile($date, $header, $workEventDays);

        $pdfFilePathWithAddedData = 'summary_events.pdf';
        $pdf->Output($pdfFilePathWithAddedData, 'F');

        return $this->file($pdfFilePathWithAddedData, 'summary_events.pdf');
    }
}
