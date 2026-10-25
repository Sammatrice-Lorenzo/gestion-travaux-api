<?php

declare(strict_types=1);

namespace App\Service\ProductInvoiceFile;

use App\Exceptions\UnsupportedImageFormatException;
use App\Service\Pdf\ImageToPdfConverter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

/**
 * Les factures produits sont toujours enregistrées en PDF : une photo envoyée depuis la PWA (ticket pris avec le
 * téléphone) est convertie, un PDF est gardé tel quel.
 */
final readonly class UploadedInvoiceFileConverter
{
    public const array PDF_MIME_TYPES = ['application/pdf', 'application/x-pdf'];

    public const array IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private ImageToPdfConverter $imageToPdfConverter,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Le PDF converti est écrit dans un dossier temporaire sous le nom « ticket.pdf » : Vich le déplace ensuite
     * à sa place définitive. Appeler removeTemporaryFile() une fois l'entité enregistrée.
     *
     * @throws UnsupportedImageFormatException image illisible ou format non pris en charge
     */
    public function toPdf(UploadedFile $file): UploadedFile|ReplacingFile
    {
        // Type déduit du contenu, pas de l'extension ni du type annoncé par le navigateur
        $mimeType = (string) $file->getMimeType();
        if (in_array($mimeType, self::PDF_MIME_TYPES, true)) {
            return $file;
        }

        $pdf = $this->imageToPdfConverter->convert($this->filesystem->readFile($file->getPathname()), $mimeType);

        $tempDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'invoice-upload-');
        $this->filesystem->remove($tempDir);
        $this->filesystem->mkdir($tempDir);

        $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $this->imageToPdfConverter->pdfFileName($file->getClientOriginalName());
        $this->filesystem->dumpFile($pdfPath, $pdf);

        return new ReplacingFile($pdfPath);
    }

    /**
     * Supprime le dossier temporaire d'un PDF converti (rien à faire pour un PDF envoyé tel quel).
     */
    public function removeTemporaryFile(File $file): void
    {
        if ($file instanceof ReplacingFile) {
            $this->filesystem->remove(dirname($file->getPathname()));
        }
    }
}
