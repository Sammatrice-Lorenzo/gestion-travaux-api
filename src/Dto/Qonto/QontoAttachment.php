<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

/**
 * Pièce jointe renvoyée par GET /v2/attachments/{id}. L'URL de téléchargement expire au bout de 30 minutes.
 */
final readonly class QontoAttachment
{
    public function __construct(
        public string $id,
        public string $fileName,
        public string $fileContentType,
        public string $url,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromArray(array $response): self
    {
        /** @var array<string, mixed> $attachment */
        $attachment = is_array($response['attachment'] ?? null) ? $response['attachment'] : [];

        return new self(
            id: (string) ($attachment['id'] ?? ''),
            fileName: (string) ($attachment['file_name'] ?? ''),
            fileContentType: (string) ($attachment['file_content_type'] ?? ''),
            url: (string) ($attachment['url'] ?? ''),
        );
    }
}
