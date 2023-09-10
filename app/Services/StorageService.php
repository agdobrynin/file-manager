<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\StorageServiceInterface;
use App\Enums\DiskEnum;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

readonly class StorageService implements StorageServiceInterface
{
    public function __construct(private Filesystem $filesystem, private DiskEnum $diskEnum)
    {
    }

    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function disk(): DiskEnum
    {
        return $this->diskEnum;
    }

    public function upload(File|UploadedFile|string $contents, string $destination): bool|string
    {
        return $this->filesystem->put($destination, $contents);
    }
}
