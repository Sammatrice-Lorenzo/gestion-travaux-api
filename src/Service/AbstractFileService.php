<?php

namespace App\Service;

use Override;
use setasign\Fpdi\Fpdi;
use App\Interface\InvoiceFileInterface;

abstract class AbstractFileService implements InvoiceFileInterface
{
    protected Fpdi $fpdi;

    protected int $rowHeightColumn = 0;

    #[Override]
    public function setFpdi(Fpdi $fpdi): void
    {
        $this->fpdi = $fpdi;
    }

    public function getFpdi(): Fpdi
    {
        return $this->fpdi;
    }

    public function setRowHeightColumn(int $rowHeightColumn): static
    {
        $this->rowHeightColumn = $rowHeightColumn;

        return $this;
    }

    public static function convertTextInUTF8(string $text): string
    {
        return iconv('UTF-8', 'windows-1252', $text);
    }

    public function getTotalColumnsWidth(): int
    {
        return array_sum($this->getColumnsWidth());
    }

    /**
     * @return int[]
     */
    abstract public function getColumnsWidth(): array;

    public static function formatFloatValue(string $value): string
    {
        $floatValue = (float) $value;

        return 0.0 === $floatValue ? $value : number_format($floatValue, 2, ',', '');
    }

    public function handleMultiLineText(string $value, float $cellWidth, string $position): void
    {
        $x = $this->fpdi->GetX();
        $y = $this->fpdi->GetY();
        $this->fpdi->MultiCell($cellWidth, $this->rowHeightColumn, self::convertTextInUTF8($value), 1, $position);
        $this->fpdi->SetY($y);
        $this->fpdi->SetX($cellWidth + $x);
    }

    /**
     * @param string[] $row
     * @param int[] $columnsWidths
     */
    public function calculateMaxHeight(array $row, array $columnsWidths): int
    {
        $maxHeight = 0;
        foreach ($row as $i => $value) {
            $textWidth = $this->getFpdi()->GetStringWidth($value);
            $cellWidth = $columnsWidths[$i];
            $height = ceil($textWidth / $cellWidth) * $this->rowHeightColumn;
            $maxHeight = max($maxHeight, $height);
        }

        return $maxHeight;
    }
}
