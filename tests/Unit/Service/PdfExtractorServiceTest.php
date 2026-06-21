<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PdfExtractorService;
use Codeception\Test\Unit;
use Symfony\Component\HttpFoundation\File\File;

final class PdfExtractorServiceTest extends Unit
{
    public function testReturnsDefaultsWhenPdfCannotBeParsed(): void
    {
        $invalidPdfPath = codecept_data_dir('invalid-invoice.pdf');
        file_put_contents($invalidPdfPath, '%PDF-1.4 invalid content');

        $service = (new PdfExtractorService())->setFile(new File($invalidPdfPath));

        $this->assertSame(0.0, $service->extractTotalFromPdf());
        $this->assertSame('', $service->extractDateFromPdf());

        unlink($invalidPdfPath);
    }
}
