<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use Override;
use App\Service\AbstractFileService;

final class PdfTableBuilder extends AbstractFileService
{
    private const int DEFAULT_ROW_HEIGHT_COLUMN = 10;

    private const int DEFAULT_X = 15;

    /**
     * @var int[]
     */
    private array $columnsWidths = [];

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
        $this->setRowHeightColumn(self::DEFAULT_ROW_HEIGHT_COLUMN);
    }

    /**
     * @param int[] $columnsWidths
     */
    public function withColumnsWidths(array $columnsWidths): static
    {
        $this->columnsWidths = $columnsWidths;

        return $this;
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

    /**
     * @return int[]
     */
    #[Override]
    public function getColumnsWidth(): array
    {
        return $this->columnsWidths;
    }

    public function build(): void
    {
        if (null !== $this->title) {
            $this->buildTitle();
        }

        $this->buildHeaderRow();
        $this->buildDataRows();
    }

    private function buildTitle(): void
    {
        $this->fpdi->SetFont('Arial', 'B', 15);
        $this->fpdi->Cell(0, 10, self::convertTextInUTF8((string) $this->title), 0, 1, 'C');
        $this->fpdi->Ln(10);
        $this->fpdi->SetFont('Arial', 'B', 12);
    }

    private function buildHeaderRow(): void
    {
        $this->fpdi->SetFillColor(0, 0, 0);
        $this->fpdi->SetTextColor(255);
        $this->fpdi->SetDrawColor(20, 0, 0);
        $this->fpdi->SetLineWidth(.3);
        $this->fpdi->SetFont('', 'B');
        $this->fpdi->SetX(self::DEFAULT_X);

        foreach ($this->header as $i => $cell) {
            $this->fpdi->Cell($this->columnsWidths[$i], 7, self::convertTextInUTF8($cell), 1, 0, 'C', true);
        }
        $this->fpdi->Ln();
    }

    private function buildDataRows(): void
    {
        $x = self::DEFAULT_X;

        $this->fpdi->SetFillColor(224, 235, 255);
        $this->fpdi->SetTextColor(0);
        $this->fpdi->SetFont('');
        $this->fpdi->SetX($x);

        foreach ($this->rows as $row) {
            $this->fpdi->SetX($x);
            $maxHeight = $this->calculateMaxHeight($row, $this->columnsWidths);

            foreach ($row as $i => $cell) {
                $this->buildCell($cell, $this->columnsWidths[$i], $maxHeight);
            }
            $this->fpdi->Ln();
        }

        $this->fpdi->SetX($x);
        $this->fpdi->Cell($this->getTotalColumnsWidth(), 0, '', 'T');
    }

    private function buildCell(string $value, int $cellWidth, int $maxHeight): void
    {
        $position = 'C';

        if ($this->fpdi->GetStringWidth($value) > $cellWidth) {
            $this->handleMultiLineText($value, $cellWidth, $position);
        } else {
            $this->fpdi->Cell($cellWidth, $maxHeight, self::convertTextInUTF8($value), 1, 0, $position);
        }
    }
}
