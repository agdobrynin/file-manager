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

    public function resolveStorage(Model $file): StorageServiceInterface
    {
        return match ($file->disk) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
        };
    }
}