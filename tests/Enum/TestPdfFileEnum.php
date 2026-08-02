<?php

declare(strict_types=1);

namespace App\Tests\Enum;

enum TestPdfFileEnum: string
{
    case INVOICE_TEMPLATE = 'InvoiceTemplate.pdf';

    public static function path(): string
    {
        return codecept_data_dir(self::INVOICE_TEMPLATE->value);
    }

    /**
     * @return array{name: string, type: string, error: int, size: false|int, tmp_name: string}
     */
    public static function uploadPayload(): array
    {
        $path = self::path();

        return [
            'name' => self::INVOICE_TEMPLATE->value,
            'type' => 'application/pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
    }
}
