<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DiskEnum;

interface StorageByDiskTypeServiceInterface
{
    public function resolve(DiskEnum $diskEnum): StorageServiceInterface;
}
