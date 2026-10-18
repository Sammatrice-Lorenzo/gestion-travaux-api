<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use App\Exceptions\UnsupportedImageFormatException;
use GdImage;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Convertit une photo (JPEG, PNG, WebP) en PDF A4 d'une page : orientation EXIF corrigée, image réduite à
 * MAX_SIDE_PX sur le grand côté, recompressée en JPEG et centrée dans la page.
 */
final readonly class ImageToPdfConverter
{
    public const int MAX_SIDE_PX = 2000;

    public const int JPEG_QUALITY = 82;

    private const float PAGE_MARGIN_MM = 10.0;

    private const float A4_SHORT_SIDE_MM = 210.0;

    private const float A4_LONG_SIDE_MM = 297.0;

    private const array JPEG_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/pjpeg'];

    private string $tempDir;

    public function __construct(
        #[Autowire('%kernel.cache_dir%/mpdf')]
        ?string $tempDir = null,
    ) {
        $this->tempDir = $tempDir ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf';
    }

    public function supports(string $mimeType): bool
    {
        return match (strtolower($mimeType)) {
            'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png' => true,
            'image/webp' => function_exists('imagecreatefromwebp'),
            default => false,
        };
    }

    /**
     * @throws UnsupportedImageFormatException
     *
     * @return string contenu binaire du PDF
     */
    public function convert(string $imageContent, string $mimeType): string
    {
        $mimeType = strtolower($mimeType);
        if (!$this->supports($mimeType)) {
            throw new UnsupportedImageFormatException($mimeType);
        }

        $image = @imagecreatefromstring($imageContent);
        if (false === $image) {
            throw new UnsupportedImageFormatException($mimeType, 'image illisible');
        }

        if (in_array($mimeType, self::JPEG_MIME_TYPES, true)) {
            $image = $this->applyExifOrientation($image, $imageContent);
        }

        $image = $this->resize($image);

        return $this->renderPdf($this->encodeJpeg($image), imagesx($image), imagesy($image));
    }

    /**
     * « ticket.jpg » devient « ticket.pdf » (nom nettoyé, utilisable comme nom de fichier).
     */
    public static function pdfFileName(string $originalName): string
    {
        $baseName = (new AsciiSlugger())->slug(pathinfo($originalName, PATHINFO_FILENAME))->toString();

        return ('' === $baseName ? 'facture' : $baseName) . '.pdf';
    }

    /**
     * Les appareils photo enregistrent souvent l'image « couchée » avec un tag EXIF : on applique la rotation.
     * Sans l'extension exif, l'image est laissée telle quelle.
     */
    private function applyExifOrientation(GdImage $image, string $imageContent): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $stream = fopen('php://memory', 'r+b');
        if (false === $stream) {
            return $image;
        }

        fwrite($stream, $imageContent);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->rotate($this->flip($image, IMG_FLIP_VERTICAL), -90),
            6 => $this->rotate($image, -90),
            7 => $this->rotate($this->flip($image, IMG_FLIP_HORIZONTAL), -90),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function flip(GdImage $image, int $mode): GdImage
    {
        imageflip($image, $mode);

        return $image;
    }

    /**
     * @param int $angle degrés, sens antihoraire (convention GD)
     */
    private function rotate(GdImage $image, int $angle): GdImage
    {
        $rotated = imagerotate($image, $angle, 0);

        return false === $rotated ? $image : $rotated;
    }

    private function resize(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longSide = max($width, $height);
        if ($longSide <= self::MAX_SIDE_PX) {
            return $image;
        }

        $ratio = self::MAX_SIDE_PX / $longSide;
        $resized = imagescale($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)), IMG_BICUBIC);

        return false === $resized ? $image : $resized;
    }

    /**
     * Recompresse en JPEG sur fond blanc (les zones transparentes d'un PNG deviennent blanches).
     */
    private function encodeJpeg(GdImage $image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);

        return (string) ob_get_clean();
    }

    /**
     * Page A4 dans le sens de l'image, image agrandie ou réduite pour tenir dans les marges et centrée.
     */
    private function renderPdf(string $jpeg, int $widthPx, int $heightPx): string
    {
        $isLandscape = $widthPx > $heightPx;
        $pageWidth = $isLandscape ? self::A4_LONG_SIDE_MM : self::A4_SHORT_SIDE_MM;
        $pageHeight = $isLandscape ? self::A4_SHORT_SIDE_MM : self::A4_LONG_SIDE_MM;

        $millimetersPerPixel = min(
            ($pageWidth - 2 * self::PAGE_MARGIN_MM) / $widthPx,
            ($pageHeight - 2 * self::PAGE_MARGIN_MM) / $heightPx,
        );
        $drawWidth = $widthPx * $millimetersPerPixel;
        $drawHeight = $heightPx * $millimetersPerPixel;

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => $isLandscape ? 'L' : 'P',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'tempDir' => $this->tempDir,
            ]);
            $mpdf->imageVars['image'] = $jpeg;
            $mpdf->AddPage($isLandscape ? 'L' : 'P');
            $mpdf->Image(
                'var:image',
                ($pageWidth - $drawWidth) / 2,
                ($pageHeight - $drawHeight) / 2,
                $drawWidth,
                $drawHeight,
                'jpg',
                '',
                true,
                false,
            );

            return $mpdf->OutputBinaryData();
        } catch (MpdfException $exception) {
            throw new UnsupportedImageFormatException('image/jpeg', 'conversion PDF impossible (' . $exception->getMessage() . ')');
        }
    }
}
