<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ProductInvoiceFile;

use App\Exceptions\UnsupportedImageFormatException;
use App\Service\Pdf\ImageToPdfConverter;
use App\Service\ProductInvoiceFile\UploadedInvoiceFileConverter;
use Codeception\Test\Unit;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

final class UploadedInvoiceFileConverterTest extends Unit
{
    private UploadedInvoiceFileConverter $converter;

    private Filesystem $filesystem;

    private string $workDir;

    protected function _before(): void
    {
        $this->filesystem = new Filesystem();
        $this->converter = new UploadedInvoiceFileConverter(new ImageToPdfConverter(new AsciiSlugger()));
        $this->workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploaded-invoice-test-' . bin2hex(random_bytes(4));
        $this->filesystem->mkdir($this->workDir);
    }

    protected function _after(): void
    {
        $this->filesystem->remove($this->workDir);
    }

    public function testPdfIsKeptAsIs(): void
    {
        $upload = $this->upload('InvoiceTemplate.pdf', 'facture.pdf');

        $this->assertSame($upload, $this->converter->toPdf($upload));
    }

    public function testPhotoIsConvertedToNamedPdf(): void
    {
        $upload = $this->upload('fakeImage.jpg', 'Ticket café.jpg');

        $pdf = $this->converter->toPdf($upload);

        $this->assertInstanceOf(ReplacingFile::class, $pdf);
        $this->assertSame('Ticket-cafe.pdf', $pdf->getClientOriginalName());
        $this->assertStringStartsWith('%PDF-', $this->filesystem->readFile($pdf->getPathname()));

        $temporaryDir = dirname($pdf->getPathname());
        $this->converter->removeTemporaryFile($pdf);
        $this->assertDirectoryDoesNotExist($temporaryDir);
    }

    public function testUnreadableImageThrows(): void
    {
        $path = $this->workDir . DIRECTORY_SEPARATOR . 'broken.jpg';
        $this->filesystem->dumpFile($path, 'pas une image');

        $this->expectException(UnsupportedImageFormatException::class);

        $this->converter->toPdf(new UploadedFile($path, 'broken.jpg', 'image/jpeg', null, true));
    }

    public function testRemovingAnUntouchedPdfKeepsIt(): void
    {
        $upload = $this->upload('InvoiceTemplate.pdf', 'facture.pdf');

        $this->converter->removeTemporaryFile($upload);

        $this->assertFileExists($upload->getPathname());
    }

    /**
     * Copie du fichier de test : un UploadedFile peut être déplacé, l'original doit rester intact.
     */
    private function upload(string $dataFile, string $clientName): UploadedFile
    {
        $path = $this->workDir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(4));
        $this->filesystem->copy(codecept_data_dir($dataFile), $path);

        return new UploadedFile($path, $clientName, null, null, true);
    }
}
