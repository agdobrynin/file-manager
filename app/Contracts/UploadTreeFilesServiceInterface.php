<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Models\File as Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

interface UploadTreeFilesServiceInterface
{
    /**
     * @param Model $parentFolder
     * @param array<string, UploadedFile[]|File[]|string> $files Binary tree with files and folders.
     * @return Collection<Model>
     * @throws RuntimeException|Throwable
     */
    public function upload(Model $parentFolder, array $files): Collection;
}
