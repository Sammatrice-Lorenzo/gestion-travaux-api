<?php

namespace App\Helper;

final class ImageHelper
{
    public static function compress(string $fileName, string $destination, int $quality): void
    {
        $file = "{$destination}/{$fileName}";
        $info = getimagesize($file);

        $image = match ($info['mime']) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($file),
            'image/gif' => imagecreatefromgif($file),
            'image/png' => imagecreatefrompng($file),
            default => ''
        };
    
        imagejpeg($image, $file, $quality);
    }
}
