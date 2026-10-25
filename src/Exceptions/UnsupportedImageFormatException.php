<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class UnsupportedImageFormatException extends Exception
{
    public function __construct(public readonly string $mimeType, string $reason = 'format non pris en charge')
    {
        parent::__construct(message: "Image « {$mimeType} » : {$reason}.");
    }
}
