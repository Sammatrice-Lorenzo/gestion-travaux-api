<?php

namespace App\Service;

use DateTime;
use Override;
use stdClass;
use App\Entity\Client;
use setasign\Fpdi\Fpdi;
use App\Helper\DateFormatHelper;
use App\Service\AbstractFileService;

final class InvoiceFileService extends AbstractFileService
{
    private const int WIDTH_COLUMN = 30;

    private const int ROW_HEIGHT_COLUMN_INVOICE_NAME = 8;

    private const float SIZE_FONT = 7.5;
    
    private const float TVA_MAINTENANCE_WORK = 10.0;

    public const int ROW_HEIGHT_COLUMN = 5;

    public function setupInvoiceParameterFile(string $pdfTemplate): void
    {
        $this->fpdi->AddPage();

        $this->fpdi->AddFont('TrebuchetMS', '', 'trebuc.php');
        $this->fpdi->AddFont('TrebuchetMS-Bold', '', 'Trebuchet-MS-Bold.php');

        $pageCount = $this->fpdi->setSourceFile($pdfTemplate);
        $tplIdx = $this->fpdi->importPage($pageCount);
        $this->fpdi->useTemplate($tplIdx, 0, 0, 210);
    }

    private function setAcknowledgment(): void
    {
        $this->fpdi->SetXY(27, $this->fpdi->GetY());

        $height = self::ROW_HEIGHT_COLUMN;
        $text = self::convertTextInUTF8('En votre aimable règlement à réception de facture');
        $this->fpdi->Cell(0, $height, $text, 0, 1);
        $this->fpdi->SetFont('TrebuchetMS-Bold', '', 8.5);

        $this->fpdi->Cell(0, $height, 'JE VOUS REMERCIE POUR VOTRE CONFIANCE', 0, 1, 'C');
    }

    /**
     * @param string[]  $headers
     */
    public function setHeaderInvoice(array $headers): void
    {
        $startX = 10;
        $startY = $this->fpdi->GetY() + 8;

        $this->fpdi->SetFont('TrebuchetMS-Bold', '', self::SIZE_FONT);
        $this->fpdi->SetXY($startX, $startY);
        $columnsWidths = $this->getColumnsWidth();
        $totalColumnsWidth = $this->getTotalColumnsWidth();

        self::setElementCenter($this->fpdi, $totalColumnsWidth);

        $this->fpdi->SetLineWidth(.3);
        $this->fpdi->SetDrawColor(54, 95, 145);
        $this->fpdi->SetFillColor(185, 200, 219);
        $this->fpdi->Rect($this->fpdi->GetX(), $this->fpdi->GetY(), self::WIDTH_COLUMN, self::ROW_HEIGHT_COLUMN, 'F');
        foreach ($headers as $i => $header) {
            $this->fpdi->SetDrawColor(54, 95, 145);
            $this->fpdi->Cell($columnsWidths[$i], self::ROW_HEIGHT_COLUMN, $header, 1, 0, 'C', 1);
            $this->fpdi->SetFillColor(185, 200, 219);
        }
        $this->fpdi->Ln();
    }

    /**
     * @param array<int, array<string, string>> $invoiceData
     */
    public function setValuesTable(array $invoiceData): void
    {
        $this->fpdi->SetDrawColor(54, 95, 145);

        $columnsWidths = $this->getColumnsWidth();
        $totalColumnsWidth = $this->getTotalColumnsWidth();

        $this->fpdi->SetFont('TrebuchetMS', '', self::SIZE_FONT);
        self::setElementCenter($this->fpdi, $totalColumnsWidth);

        $total = 0.0;
        foreach ($invoiceData as $row) {
            self::setElementCenter($this->fpdi, $totalColumnsWidth);
            $maxHeight = $this->calculateMaxHeight($row, $columnsWidths);

            foreach ($row as $i => $cell) {
                $value = self::formatFloatValue($cell);
                $position = self::getPositionTextInCell((int) $i, $row);
                $cellWidth = $columnsWidths[$i];

                if ($this->fpdi->GetStringWidth($value) > $cellWidth) {
                    $this->handleMultiLineText($value, $cellWidth, $position);
                } else {
                    $this->fpdi->Cell($cellWidth, $maxHeight, self::convertTextInUTF8($value), 1, 0, $position);
                }
            }

            $total += (float) end($row);
            $this->fpdi->Ln();
        }

        $this->setTotalOfInvoice($total);
        $this->fpdi->Ln();
    }

    private function setTotalOfInvoice(float $sumOfTotal): void
    {
        $columnsWidth = $this->getColumnsWidth();
        $totalColumnsWidth = $this->getTotalColumnsWidth();

        $lineTotal = ['', 'Main-d\'oeuvre et diverses fournitures', 'Ensemble', $sumOfTotal];
        self::setElementCenter($this->fpdi, $totalColumnsWidth);

        foreach ($lineTotal as $index => $element) {
            $position = self::getPositionTextInCell($index, $lineTotal);
            $value = self::formatFloatValue($element);

            $this->fpdi->Cell(
                $columnsWidth[$index],
                self::ROW_HEIGHT_COLUMN,
                self::convertTextInUTF8($value),
                1,
                0,
                $position
            );
        }
        $this->fpdi->Ln();

        $this->setTVAInvoice($sumOfTotal);
    }

    private function setTVAInvoice(float $sumOfTotal): void
    {
        $columnsWidth = $this->getColumnsWidth();
        $lastColumnsWidth = array_slice($columnsWidth, -2, 2, true);

        $lastColumnsWidth = array_values($lastColumnsWidth);
        $priceOfTVA = (self::TVA_MAINTENANCE_WORK / 100) * $sumOfTotal;
        $sumTotalWithTVA = $sumOfTotal + $priceOfTVA;

        $prices = [
            ['SOUS-TOTAL', ''],
            ['T.V.A ' . self::TVA_MAINTENANCE_WORK . ' %', $priceOfTVA],
            ['TOTAL', $sumTotalWithTVA],
        ];
        foreach ($prices as $price) {
            $this->setElementOfLastColumn(array_sum($lastColumnsWidth));
            foreach ($price as $key => $cell) {
                $border = $key === array_key_first($price) ? 0 : 1;
                $value = self::formatFloatValue($cell);

                $this->fpdi->Cell(
                    $lastColumnsWidth[$key],
                    self::ROW_HEIGHT_COLUMN,
                    $value,
                    $border,
                    0,
                    'R'
                );
            }
            $this->fpdi->Ln();
        }

        $this->setAcknowledgment();
    }

    private function setNameInvoice(string $nameInvoice): void
    {
        $this->fpdi->SetFont('TrebuchetMS-Bold', '', 14);
        $this->fpdi->SetXY(50, 87);
        $totalColumnsWidth = $this->getTotalColumnsWidth();

        self::setElementCenter($this->fpdi, $totalColumnsWidth);

        $this->fpdi->SetLineWidth(.3);
        $this->fpdi->SetDrawColor(54, 95, 145);
        $this->fpdi->SetFillColor(185, 200, 219);
        $this->fpdi->Rect(
            $this->fpdi->GetX(),
            $this->fpdi->GetY(),
            self::WIDTH_COLUMN,
            self::ROW_HEIGHT_COLUMN_INVOICE_NAME,
            'F'
        );
        $this->fpdi->SetDrawColor(54, 95, 145);
        $this->fpdi->Cell($totalColumnsWidth, self::ROW_HEIGHT_COLUMN_INVOICE_NAME, 'FACTURE', 1, 0, 'C', 1);
        $this->fpdi->Ln();

        $this->fpdi->SetFont('TrebuchetMS', '', self::SIZE_FONT);
        $nameInvoice = self::convertTextInUTF8($nameInvoice);
        self::setElementCenter($this->fpdi, $totalColumnsWidth);
        
        $this->fpdi->SetFillColor(255, 255, 255);
        $this->fpdi->MultiCell($totalColumnsWidth, self::ROW_HEIGHT_COLUMN_INVOICE_NAME, $nameInvoice, 1, 'C');
    }

    /**
     * @return int[]
     */
    #[Override]
    public function getColumnsWidth(): array
    {
        return [40, 67, 30, 30];
    }

    private static function setElementCenter(Fpdi $pdf, int $totalColumnsWidth): void
    {
        $pdf->SetX(($pdf->GetPageWidth() - $totalColumnsWidth + 10) / 2);
    }

    private function setElementOfLastColumn(int $lastColumnsWidth): void
    {
        $this->fpdi->SetX(($this->fpdi->GetPageWidth() + $lastColumnsWidth - 3) / 2);
    }

    /**
     * @param int $key
     * @param mixed[] $elements
     *
     * @return string
     */
    private static function getPositionTextInCell(int $key, array $elements): string
    {
        return $key === array_key_last($elements) ? 'R' : 'L';
    }

    private function setClient(Client $client): void
    {
        $this->setElementCenter($this->fpdi, self::getTotalColumnsWidth());
        $this->fpdi->SetXY(25, $this->fpdi->GetY() + 20);
        $this->fpdi->SetFont('TrebuchetMS-Bold', '', self::SIZE_FONT);

        $height = 3;
        $nameClient = self::convertTextInUTF8("À {$client->getName()}");
        $address = self::convertTextInUTF8(
            "{$client->getStreetAddress()} {$client->getPostalCode()} {$client->getCity()}"
        );
        $this->fpdi->SetX(120);
        $this->fpdi->Cell(20, 2, self::convertTextInUTF8("Tél : {$client->getPhoneNumber()}"), 0, 1);
        $this->fpdi->SetX(25);
        $this->fpdi->Cell(20, $height, $nameClient, 0, 1);
        $this->fpdi->Ln();
        $this->fpdi->SetX(25);
        $this->fpdi->MultiCell(45, $height, $address, 0, 1);
    }

    private function setDate(): void
    {
        $date = new DateTime();
        $this->fpdi->SetFont('TrebuchetMS', '', 8);
        $this->fpdi->SetTextColor(0, 0, 0);
        $this->fpdi->SetXY(190, 40);
        $this->fpdi->Cell(5, 5, self::convertTextInUTF8("N °{$date->format('y.m/d')}"), 0, 1, 'R');
        $this->fpdi->SetXY(185, 45);
        $this->fpdi->Cell(5, 5, "DATE : {$date->format(DateFormatHelper::FRENCH_FORMAT)}", 0, 1, 'R');
    }

    /**
     * @param string[] $headers
     * @param stdClass $invoiceData
     */
    public function generateInvoiceFile(Client $client, array $headers, stdClass $invoiceData): void
    {
        $this->setDate();
        $this->setClient($client);
        $this->setNameInvoice($invoiceData->nameInvoice);
        $this->setHeaderInvoice($headers);
        $this->setValuesTable($invoiceData->invoiceLines);
    }
}
