<?php

namespace App\Service;

use ZipArchive;
use App\Exceptions\ZipArchiveException;

final readonly class ZipService
{
    /**
     * @param string[] $files
     *
     * @throws ZipArchiveException
     */
    public static function getZipArchive(array $files, string $nameZip, string $nameFolder): string
    {
        $zipFile = tempnam(sys_get_temp_dir(), $nameZip) . '.zip';
        $zip = new ZipArchive();
        if (true !== $zip->open($zipFile, ZipArchive::CREATE)) {
            throw new ZipArchiveException();
        }

        $nameFolder = rtrim($nameFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($files as $file) {
            $path = "{$nameFolder}{$file}";
            if (file_exists($path)) {
                $fileName = basename($path);
                $zip->addFile($path, $fileName);
            }
        }

        $zip->close();

        return $zipFile;
    }
}
