<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

final class QontoApiException extends InvoicingPlatformException
{
    public function __construct(string $message, int $statusCode = 0, ?Throwable $previous = null)
    {
        parent::__construct(message: $message, code: $statusCode, previous: $previous);
    }

    public static function fromStatusCode(int $statusCode, ?Throwable $previous = null): self
    {
        $message = match ($statusCode) {
            401 => 'Qonto : authentification refusée, vérifier QONTO_LOGIN et QONTO_SECRET_KEY.',
            403 => 'Qonto : accès refusé à cette ressource.',
            404 => 'Qonto : ressource introuvable.',
            429 => 'Qonto : trop de requêtes, limite atteinte malgré les réessais.',
            default => "Qonto : erreur HTTP {$statusCode}.",
        };

        return new self($message, $statusCode, $previous);
    }
}
