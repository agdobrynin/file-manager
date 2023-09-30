<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DiskEnum;

interface StorageByDiskTypeServiceInterface
{
    public function resolveStorage(DiskEnum $diskEnum): StorageServiceInterface;
}
