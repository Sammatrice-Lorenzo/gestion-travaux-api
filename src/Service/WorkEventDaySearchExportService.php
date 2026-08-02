<?php

declare(strict_types=1);

namespace App\Service;

use setasign\Fpdi\Fpdi;
use App\Entity\WorkEventDay;
use App\Service\Pdf\PdfTableBuilder;
use App\Formatter\WorkEventDaysFormatter;
use App\Service\Spreadsheet\SpreadsheetTableBuilder;

final class WorkEventDaySearchExportService
{
    /**
     * @var string[]
     */
    private const array HEADER = ['Prestation', 'Début', 'Fin', 'Client'];

    /**
     * @var int[]
     */
    private const array COLUMNS_WIDTHS = [70, 35, 35, 50];

    public function __construct(
        private readonly PdfTableBuilder $pdfTableBuilder,
        private readonly SpreadsheetTableBuilder $spreadsheetTableBuilder,
    ) {}

    /**
     * @param WorkEventDay[] $workEventDays
     */
    public function export(array $workEventDays, string $format): string
    {
        return match ($format) {
            'xlsx' => $this->exportXlsx($workEventDays),
            default => $this->exportPdf($workEventDays),
        };
    }

    /**
     * @param WorkEventDay[] $workEventDays
     */
    private function exportPdf(array $workEventDays): string
    {
        $filePath = $this->generateFilePath('pdf');

        $pdf = new Fpdi();
        $pdf->AddPage();

        $this->pdfTableBuilder->setFpdi($pdf);
        $this->pdfTableBuilder
            ->withTitle('Recherche de prestations')
            ->withColumnsWidths(self::COLUMNS_WIDTHS)
            ->withHeader(self::HEADER)
            ->withRows(WorkEventDaysFormatter::getWorkDayEventFormattedForSearchExport($workEventDays))
            ->build()
        ;

        $pdf->Output($filePath, 'F');

        return $filePath;
    }

    /**
     * @param WorkEventDay[] $workEventDays
     */
    private function exportXlsx(array $workEventDays): string
    {
        $filePath = $this->generateFilePath('xlsx');

        $this->spreadsheetTableBuilder
            ->withTitle('Recherche de prestations')
            ->withHeader(self::HEADER)
            ->withRows(WorkEventDaysFormatter::getWorkDayEventFormattedForSearchExport($workEventDays))
            ->build()
        ;
        $this->spreadsheetTableBuilder->save($filePath);

        return $filePath;
    }

    private function generateFilePath(string $extension): string
    {
        return sprintf('%s/work_event_day_search_%s.%s', sys_get_temp_dir(), uniqid('', true), $extension);
    }
}
