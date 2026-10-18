<?php

declare(strict_types=1);

namespace App\Dto\InvoicingPlatform;

/**
 * Pièce jointe d'une facture sur la plateforme agréée. L'URL de téléchargement peut expirer.
 */
final readonly class PlatformAttachment
{
    public function __construct(
        public string $id,
        public string $fileName,
        public string $fileContentType,
        public string $url,
    ) {}
}
