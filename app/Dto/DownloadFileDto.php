<?php
declare(strict_types=1);

namespace App\Dto;

readonly class DownloadFileDto
{
    public function __construct(public string $fileName, public string $storagePath)
    {
    }
}
