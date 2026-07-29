<?php

declare(strict_types=1);

namespace App\Service\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

final class SpreadsheetTableBuilder
{
    private Spreadsheet $spreadsheet;

    /**
     * @var string[]
     */
    private array $header = [];

    /**
     * @var array<array<int, string>>
     */
    private array $rows = [];

    private ?string $title = null;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * @param string[] $header
     */
    public function withHeader(array $header): static
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @param array<array<int, string>> $rows
     */
    public function withRows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function withTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function build(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $columnCount = count($this->header);
        $rowIndex = 1;

        if (null !== $this->title) {
            $lastColumnLetter = Coordinate::stringFromColumnIndex(max($columnCount, 1));
            $sheet->mergeCells("A{$rowIndex}:{$lastColumnLetter}{$rowIndex}");
            $sheet->setCellValue("A{$rowIndex}", $this->title);
            $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            ++$rowIndex;
        }

        foreach ($this->header as $columnIndex => $label) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue("{$columnLetter}{$rowIndex}", $label);
            $sheet->getStyle("{$columnLetter}{$rowIndex}")->getFont()->setBold(true);
        }
        ++$rowIndex;

        foreach ($this->rows as $row) {
            foreach ($row as $columnIndex => $value) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValue("{$columnLetter}{$rowIndex}", $value);
            }
            ++$rowIndex;
        }

        for ($columnIndex = 1; $columnIndex <= max($columnCount, 1); ++$columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
    }

    public function save(string $filePath): void
    {
        IOFactory::createWriter($this->spreadsheet, 'Xlsx')->save($filePath);
    }
}
