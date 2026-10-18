<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Pdf;

use App\Exceptions\UnsupportedImageFormatException;
use App\Service\Pdf\ImageToPdfConverter;
use Codeception\Test\Unit;
use GdImage;

final class ImageToPdfConverterTest extends Unit
{
    private const string A4_PORTRAIT_MEDIABOX = '/MediaBox [0 0 595.280 841.890]';

    private const string A4_LANDSCAPE_MEDIABOX = '/MediaBox [0 0 841.890 595.280]';

    private ImageToPdfConverter $converter;

    protected function _before(): void
    {
        $this->converter = new ImageToPdfConverter();
    }

    public function testJpegBecomesSinglePageA4Pdf(): void
    {
        $pdf = $this->converter->convert($this->jpeg(800, 600), 'image/jpeg');

        $this->assertValidSinglePagePdf($pdf);
        $this->assertStringContainsString(self::A4_LANDSCAPE_MEDIABOX, $pdf);
    }

    public function testPortraitImageUsesPortraitPage(): void
    {
        $pdf = $this->converter->convert($this->jpeg(600, 900), 'image/jpeg');

        $this->assertStringContainsString(self::A4_PORTRAIT_MEDIABOX, $pdf);
    }

    public function testExifOrientationRotatesPortraitPhoto(): void
    {
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('Extension exif absente (activée dans l\'image Docker).');
        }

        // Photo enregistrée « couchée » (400x200) avec Orientation=6 : à afficher tournée de 90° en portrait.
        $pdf = $this->converter->convert($this->withExifOrientation($this->jpeg(400, 200), 6), 'image/jpeg');

        $this->assertValidSinglePagePdf($pdf);
        $this->assertStringContainsString(self::A4_PORTRAIT_MEDIABOX, $pdf);
        $this->assertMatchesRegularExpression('#/Width 200\b#', $pdf);
        $this->assertMatchesRegularExpression('#/Height 400\b#', $pdf);
    }

    public function testLargeImageIsDownscaled(): void
    {
        $pdf = $this->converter->convert($this->jpeg(4000, 3000), 'image/jpeg');

        $this->assertMatchesRegularExpression('#/Width ' . ImageToPdfConverter::MAX_SIDE_PX . '\b#', $pdf);
        $this->assertMatchesRegularExpression('#/Height 1500\b#', $pdf);
    }

    public function testTransparentPngIsConverted(): void
    {
        $image = imagecreatetruecolor(300, 300);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 50, 50, 250, 250, (int) imagecolorallocate($image, 0, 120, 255));

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        $this->assertValidSinglePagePdf($this->converter->convert($png, 'image/png'));
    }

    public function testUnsupportedFormatThrows(): void
    {
        $this->assertFalse($this->converter->supports('image/heic'));

        $this->expectException(UnsupportedImageFormatException::class);

        $this->converter->convert('not an image', 'image/heic');
    }

    public function testUnreadableImageThrows(): void
    {
        $this->expectException(UnsupportedImageFormatException::class);

        $this->converter->convert('not a jpeg', 'image/jpeg');
    }

    public function testPdfFileName(): void
    {
        $this->assertSame('ticket.pdf', ImageToPdfConverter::pdfFileName('ticket.jpg'));
        $this->assertSame('Ticket-Cafe-ete.pdf', ImageToPdfConverter::pdfFileName('Ticket Café été.JPG'));
        $this->assertSame('facture.pdf', ImageToPdfConverter::pdfFileName('.png'));
    }

    private function assertValidSinglePagePdf(string $pdf): void
    {
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match_all('#/Type\s*/Page\b#', $pdf));
    }

    private function jpeg(int $width, int $height): string
    {
        $image = $this->image($width, $height);

        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function image(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 230, 230, 230));

        return $image;
    }

    /**
     * Insère un segment APP1 EXIF minimal (une seule entrée : Orientation) juste après le marqueur SOI.
     */
    private function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'II' . pack('v', 42) . pack('V', 8)
            . pack('v', 1)
            . pack('vvV', 0x0112, 3, 1) . pack('v', $orientation) . "\0\0"
            . pack('V', 0);
        $app1 = "Exif\0\0" . $tiff;

        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', \strlen($app1) + 2) . $app1 . substr($jpeg, 2);
    }
}
