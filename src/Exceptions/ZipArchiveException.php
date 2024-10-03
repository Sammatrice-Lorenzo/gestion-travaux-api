<?php

namespace App\Exceptions;

use Exception;

final class ZipArchiveException extends Exception
{
    public function __construct($message = 'Could not create ZIP archive')
    {
        parent::__construct($message);
    }
}
