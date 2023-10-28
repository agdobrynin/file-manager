<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\DiskEnum;

readonly class DestroyFileFromStorageDto
{
    public function __construct(
        public DiskEnum $disk,
        public string $fileStoragePath
    ) {
    }
}
