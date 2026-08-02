<?php

namespace App\Service;

use DateTime;
use Override;
use App\Helper\DateHelper;
use App\Entity\WorkEventDay;
use App\Service\Pdf\PdfTableBuilder;
use App\Formatter\WorkEventDaysFormatter;
use App\Helper\DateFormatHelper;

final class WorkEventDayFileService extends AbstractFileService
{
    public function __construct(
        private readonly PdfTableBuilder $pdfTableBuilder,
    ) {}

    /**
     * @return int[]
     */
    #[Override]
    public function getColumnsWidth(): array
    {
        return [25, 60, 27, 27, 41];
    }

    private function setHeader(DateTime $date): void
    {
        $frenchMonth = self::convertTextInUTF8(DateHelper::FRENCH_MONTHS[(string) $date->format(DateFormatHelper::MONTH_FORMAT)]);

        $this->fpdi->SetFont('Arial', 'B', 15);
        $this->fpdi->Cell(
            0,
            10,
            "Prestations du mois de {$frenchMonth} {$date->format(DateFormatHelper::YEAR_FORMAT)}",
            0,
            1,
            'C'
        );
        $this->fpdi->Ln(10);
        $this->fpdi->SetFont('Arial', 'B', 12);
    }

    /**
     * @param string[] $header
     * @param WorkEventDay[] $workEventDays
     */
    private function setTableEvents(array $header, array $workEventDays): void
    {
        $this->pdfTableBuilder->setFpdi($this->fpdi);
        $this->pdfTableBuilder
            ->withColumnsWidths($this->getColumnsWidth())
            ->withHeader($header)
            ->withRows(WorkEventDaysFormatter::getWorkDayEventFormattedForFile($workEventDays))
            ->build()
        ;
    }

    /**
     * @param string[] $header
     * @param WorkEventDay[] $workEventDays
     */
    public function generateFile(DateTime $date, array $header, array $workEventDays): void
    {
        $this->fpdi->AddPage();

        $this->setHeader($date);
        $this->setTableEvents($header, $workEventDays);
    }
}
