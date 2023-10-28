<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DiskEnum;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

interface StorageServiceInterface
{
    public function upload(File|UploadedFile|string $contents, string $destination): bool|string;

    public function filesystem(): Filesystem;

    public function disk(): DiskEnum;
}
