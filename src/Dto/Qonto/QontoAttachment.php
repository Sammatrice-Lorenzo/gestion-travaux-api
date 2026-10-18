<?php

declare(strict_types=1);

namespace App\Dto\Qonto;

use App\Exceptions\QontoApiException;

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
     *
     * @throws QontoApiException si la réponse ne contient pas de pièce jointe exploitable (id et url requis)
     */
    public static function fromArray(array $response): self
    {
        /** @var array<string, mixed> $attachment */
        $attachment = is_array($response['attachment'] ?? null) ? $response['attachment'] : [];

        $id = self::string($attachment['id'] ?? null);
        $url = self::string($attachment['url'] ?? null);
        if ('' === $id || '' === $url) {
            throw new QontoApiException('Qonto : réponse de pièce jointe inattendue (id ou url manquant).');
        }

        return new self(
            id: $id,
            fileName: self::string($attachment['file_name'] ?? null),
            fileContentType: self::string($attachment['file_content_type'] ?? null),
            url: $url,
        );
    }

    private static function string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
