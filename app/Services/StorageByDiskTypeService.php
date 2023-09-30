<?php

namespace App\Services;

use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Enums\DiskEnum;
use RuntimeException;

final readonly class StorageByDiskTypeService implements StorageByDiskTypeServiceInterface
{
    public function __construct(
        private StorageLocalServiceInterface $localService,
        private StorageCloudServiceInterface $cloudService,
    )
    {
    }

    public function resolve(DiskEnum $diskEnum): StorageServiceInterface
    {
        return match ($diskEnum) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
            default => throw new RuntimeException('Unsupported disk type')
        };
    }
}
