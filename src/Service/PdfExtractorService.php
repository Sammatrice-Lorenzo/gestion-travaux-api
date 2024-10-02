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
            if (preg_match('/Total TTC\s*(\d+,\d{2})/', $text, $matches)) {
                $totalSum += floatval(str_replace(',', '.', $matches[1]));
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

        return $date;
    }
}
