<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Models\File;
use App\Services\Exceptions\FileContentNotFoundException;

readonly class GetFileContent implements GetFileContentInterface
{
    public function __construct(
        private StorageByDiskTypeServiceInterface $storageByModelService
    ) {
    }

    public function getContent(File $file): ?string
    {
        return $this->storageByModelService
            ->resolve($file->disk)
            ->filesystem()
            ->get($file->storage_path) ?: throw new FileContentNotFoundException();
    }
}
