<?php

declare(strict_types=1);

namespace App\Helpers;

class PhpConfig
{
    public static function maxUploadFiles(): ?int
    {
        return (int) ini_get('max_file_uploads') ?: null;
    }

    public static function maxUploadFileBytes(): ?int
    {
        return Str::toByte(ini_get('upload_max_filesize') ?: '');
    }

    public static function maxPostBytes(): ?int
    {
        return Str::toByte(ini_get('post_max_size') ?: '');
    }
}
