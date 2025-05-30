<?php

namespace App\Service;

use DateTime;
use Override;
use App\Helper\DateHelper;
use App\Entity\WorkEventDay;
use App\Formatter\WorkEventDaysFormatter;
use App\Helper\DateFormatHelper;

final class WorkEventDayFileService extends AbstractFileService
{
    private const int DEFAULT_X = 15;

    public const int ROW_HEIGHT_COLUMN = 10;

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
        $this->fpdi->SetFillColor(0, 0, 0);
        $this->fpdi->SetTextColor(255);
        $this->fpdi->SetDrawColor(20, 0, 0);

        $this->fpdi->SetLineWidth(.3);
        $this->fpdi->SetFont('', 'B');
        $this->fpdi->SetX(self::DEFAULT_X);
        
        // En-tête
        $columnsWidths = $this->getColumnsWidth();

        for ($i = 0; $i < count($header); ++$i) {
            $this->fpdi->Cell($columnsWidths[$i], 7, self::convertTextInUTF8($header[$i]), 1, 0, 'C', true);
        }
        $this->fpdi->Ln();

        $this->setData($workEventDays);
    }

    private function setCelles(string $value, int $cellWidth, int $maxHeight): void
    {
        $fpdi = $this->getFpdi();
        $position = 'C';

        if ($fpdi->GetStringWidth($value) > $cellWidth) {
            $this->handleMultiLineText($value, $cellWidth, $position);
        } else {
            $fpdi->Cell($cellWidth, $maxHeight, self::convertTextInUTF8($value), 1, 0, $position);
        }
    }

    /**
     * @param WorkEventDay[] $workEventDays
     */
    private function setData(array $workEventDays): void
    {
        $columnsWidths = $this->getColumnsWidth();
        $x = self::DEFAULT_X;

        $this->fpdi->SetFillColor(224, 235, 255);
        $this->fpdi->SetTextColor(0);
        $this->fpdi->SetFont('');

        $this->fpdi->SetX($x);

        $events = WorkEventDaysFormatter::getWorkDayEventFormattedForFile($workEventDays);
        foreach ($events as $event) {
            $this->fpdi->SetX($x);
            $maxHeight = $this->calculateMaxHeight($event, $columnsWidths);

            foreach ($event as $i => $cell) {
                $this->setCelles($cell, $columnsWidths[$i], $maxHeight);
            }
            $this->fpdi->Ln();
        }

        $this->fpdi->SetX($x);
        $this->fpdi->Cell(self::getTotalColumnsWidth(), 0, '', 'T');
    }

    /**
     * @param DateTime $date
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
