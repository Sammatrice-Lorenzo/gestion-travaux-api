<?php

namespace App\Exceptions;

use Exception;

final class ZipArchiveException extends Exception
{
    public function __construct(string $message = 'Could not create ZIP archive')
    {
        parent::__construct(message: $message);
    }
}
