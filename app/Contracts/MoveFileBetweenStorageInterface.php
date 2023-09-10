<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Models\File;
use Throwable;

interface MoveFileBetweenStorageInterface
{
    public function storageServiceFrom(): StorageServiceInterface;

    public function storageServiceTo(): StorageServiceInterface;

    /**
     * @throws Throwable
     */
    public function move(File $model): File;
}
