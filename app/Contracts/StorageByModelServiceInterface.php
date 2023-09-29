<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DiskEnum;
use Illuminate\Database\Eloquent\Model;

interface StorageByModelServiceInterface
{
    public function resolveStorage(DiskEnum|Model $file): StorageServiceInterface;
}