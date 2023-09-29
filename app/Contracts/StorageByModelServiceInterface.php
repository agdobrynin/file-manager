<?php
declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface StorageByModelServiceInterface
{
    public function resolveStorage(Model $file): StorageServiceInterface;
}