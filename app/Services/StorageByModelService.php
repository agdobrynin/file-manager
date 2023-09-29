<?php

namespace App\Services;

use App\Contracts\StorageByModelServiceInterface;
use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Enums\DiskEnum;
use Illuminate\Database\Eloquent\Model;

final readonly class StorageByModelService implements StorageByModelServiceInterface
{
    public function __construct(
        private StorageLocalServiceInterface $localService,
        private StorageCloudServiceInterface $cloudService,
    )
    {
    }

    public function resolveStorage(DiskEnum|Model $file): StorageServiceInterface
    {
        $disk = $file instanceof Model ? $file->disk : $file;

        return match ($disk) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
        };
    }
}