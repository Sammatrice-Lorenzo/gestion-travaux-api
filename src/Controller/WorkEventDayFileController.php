<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use setasign\Fpdi\Fpdi;
use App\Service\ApiService;
use App\Service\WorkEventDayFileService;
use App\Repository\WorkEventDayRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Checker\WorkEventDay\WorkEventDayFileAPIChecker;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class WorkEventDayFileController extends AbstractController
{
    public function __construct(
        private readonly WorkEventDayRepository $workEventDayRepository,
        private readonly ApiService $apiService
    ) {
    }

    #[Route('/api/work-event-day/file', name: 'app_work_event_day_file', methods: ['POST'])]
    public function downloadFileEvents(
        Request $request,
        WorkEventDayFileService $workEventDayFileService,
        WorkEventDayFileAPIChecker $workEventDayFileAPIChecker
    ): JsonResponse|BinaryFileResponse
    {
        $pdf = new Fpdi();

        $data = json_decode($request->getContent());
        $errorsBody = $workEventDayFileAPIChecker->checkBodyAPI($request);
        if ($errorsBody->getContent() !== '[]') {
            return $errorsBody;
        }

        /** @var User $user */
        $user = $this->getUser();
        $date = (new DateTime(str_replace('/', '-', $data->date)));
        $workEventDays = $this->workEventDayRepository->findByMonth($user, $date);

        $header = ['Date', 'Prestation', 'Début', 'Fin'];

        $workEventDayFileService->setFpdi($pdf);
        $workEventDayFileService->generateFile($date, $header, $workEventDays);

        $pdfFilePathWithAddedData = 'summary_events.pdf';
        $pdf->Output($pdfFilePathWithAddedData, 'F');

        return $this->file($pdfFilePathWithAddedData, 'summary_events.pdf');
    }
}
