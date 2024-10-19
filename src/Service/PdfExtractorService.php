<?php

namespace App\Service;

use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;
use Symfony\Component\HttpFoundation\File\File;

final class PdfExtractorService
{
    private Parser $parser;

    private File $file;

    public function __construct() {
        $this->parser = new Parser();
    }

    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    private function getPdf(): Document
    {
        return $this->parser->parseFile($this->file->getPathname());
    }

    private function getTextPdf(): string
    {
        $pdf = $this->getPdf();
        
        return $pdf->getText();
    }

    public function extractTotalFromPdf(): ?float
    {
        $totalSum = 0.0;
        $pdf = $this->getPdf();
        foreach ($pdf->getPages() as $page) {
            $text = $page->getText();
            $hasTotalTTCWithComa = preg_match('/Total TTC\s*(\d+,\d{2})/', $text, $matches);
            $hasTotalTTCWithPoint = preg_match('/Total TTC\s*(\d+.\d{2})/', $text, $matches);

            if ($hasTotalTTCWithComa || $hasTotalTTCWithPoint) {
                $totalSum += floatval(str_replace(',', '.', $matches[1]));
            } else {
                $this->extractAmountInSameRow($text, $totalSum);
            }
        }

        return $totalSum;
    }

    public function extractDateFromPdf(): string
    {
        $text = $this->getTextPdf();
        $date = '';

        if (preg_match('/Date de vente :\s*(\d{1,2}\/\d{1,2}\/\d{2,4}|\d{4}-\d{1,2}-\d{1,2})/', $text, $matches)) {
            $date = $matches[1];
        }

        if (preg_match('/Date :\s*(\d{1,2}\/\d{1,2}\/\d{2,4}|\d{4}-\d{1,2}-\d{1,2})/', $text, $matches)) {
            $date = $matches[1];
        }

        if (preg_match('/Date d\'émission :\s*(\d{1,2}\/\d{1,2}\/\d{2,4}|\d{4}-\d{1,2}-\d{1,2})/', $text, $matches)) {
            $date = $matches[1];
        }

        if (preg_match('/Date du\s+réglement\s*:\s*(?:\n|\r|\s)*(\d{1,2}\/\d{1,2}\/\d{2,4}|\d{4}-\d{1,2}-\d{1,2})/', $text, $matches)) {
            $date = $matches[1];
        }

        return $date;
    }

    private function extractAmountInSameRow(string $text, float &$totalSum): void
    {
        if (preg_match('/TOTAL.*/', $text, $matches)) {
            $totalTTC = $matches[0];
            if (preg_match('/TOTAL\s+(\d{1,2},\d{2})\s+(\d{1,2},\d{2})\s+(\d{1,2},\d{2})/', $totalTTC, $matches)) {
                $totalSum += floatval(str_replace(',', '.', end($matches)));
            }
        }
    }
}
