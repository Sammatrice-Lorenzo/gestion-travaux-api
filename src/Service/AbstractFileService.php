<?php

namespace App\Service;

use Override;
use setasign\Fpdi\Fpdi;
use App\Interface\InvoiceFileInterface;

abstract class AbstractFileService implements InvoiceFileInterface
{
    protected Fpdi $fpdi;

    #[Override]
    public function setFpdi(Fpdi $fpdi): void
    {
        $this->fpdi = $fpdi;
    }

    public function getFpdi(): Fpdi
    {
        return $this->fpdi;
    }

    public static function convertTextInUTF8(string $text): string
    {
        return iconv('UTF-8', 'windows-1252', $text);
    }

    /**
     * @return integer
     */
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

        return $floatValue === 0.0 ? $value : number_format($floatValue, 2, ',', '');
    }

    public function handleMultiLineText(string $value, float $cellWidth, string $position): void
    {
        $calledClass = get_called_class();

        $x = $this->fpdi->GetX();
        $y = $this->fpdi->GetY();
        $this->fpdi->MultiCell($cellWidth, $calledClass::ROW_HEIGHT_COLUMN, self::convertTextInUTF8($value), 1, $position);
        $this->fpdi->SetY($y);
        $this->fpdi->SetX($cellWidth + $x);
    }

    /**
     * @param string[] $row
     * @param int[] $columnsWidths
     * @return integer
     */
    public function calculateMaxHeight(array $row, array $columnsWidths): int
    {
        $calledClass = get_called_class();

        $maxHeight = 0;
        foreach ($row as $i => $value) {
            $textWidth = $this->getFpdi()->GetStringWidth($value);
            $cellWidth = $columnsWidths[$i];
            $height = ceil($textWidth / $cellWidth) * $calledClass::ROW_HEIGHT_COLUMN;
            $maxHeight = max($maxHeight, $height);
        }

        return $maxHeight;
    }
}
