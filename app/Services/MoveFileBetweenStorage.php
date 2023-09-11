<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Contracts\StorageServiceInterface;
use App\Models\File;
use Throwable;

readonly class MoveFileBetweenStorage implements MoveFileBetweenStorageInterface
{
    public function __construct(private StorageServiceInterface $from, private StorageServiceInterface $to)
    {
    }

    /**
     * @throws Throwable
     */
    public function move(File $model): File
    {
        $contents = $this->from->filesystem()->get($model->path);

        throw_unless(
            $this->to->filesystem()->put($model->path, $contents),
            message: 'Can\'t move file'
        );

        $model->disk = $this->to->disk();
        $model->saveQuietly();

        $this->from->filesystem()->delete($model->path);

        $directory = dirname($model->path);

        if (0 === count($this->from->filesystem()->files($directory, true))) {
            $this->from->filesystem()->deleteDirectory($directory);
        }

        return $model;
    }

    public function storageServiceFrom(): StorageServiceInterface
    {
        return $this->from;
    }

    public function storageServiceTo(): StorageServiceInterface
    {
        return $this->to;
    }
}