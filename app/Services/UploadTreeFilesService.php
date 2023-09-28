<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\StorageServiceInterface;
use App\Contracts\UploadTreeFilesServiceInterface;
use App\Models\File as Model;
use App\VO\FileFolderVO;
use App\VO\FileVO;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Throwable;

readonly class UploadTreeFilesService implements UploadTreeFilesServiceInterface
{
    public function __construct(private StorageServiceInterface $storageService)
    {
    }

    /**
     * @param Model $parentFolder
     * @param array<string, UploadedFile[]|File[]|string> $files Binary tree with files and folders.
     * @return Collection<Model>
     * @throws Throwable
     */
    public function upload(Model $parentFolder, array $files): Collection
    {
        throw_unless($parentFolder->isFolder(), message: 'Parent element must be is folder');

        return $this->make($parentFolder, $files, collect());
    }

    protected function make(Model $parentFolder, array $files, Collection $models): Collection
    {
        foreach ($files as $key => $item) {
            if ($item instanceof UploadedFile || $item instanceof File) {
                $separator = str_starts_with($parentFolder->path ?? '/', DIRECTORY_SEPARATOR)
                    ? ''
                    : DIRECTORY_SEPARATOR;

                $destinationDirectoryStorage = 'files' .
                    DIRECTORY_SEPARATOR .
                    $parentFolder->created_by .
                    $separator .
                    $parentFolder->path;

                $storagePath = $this->storageService->upload($item, $destinationDirectoryStorage);

                $fileVO = new FileVO(
                    name: $item->getClientOriginalName(),
                    mime: $item->getClientMimeType(),
                    size: $item->getSize(),
                    disk: $this->storageService->disk(),
                    path: null,
                    storagePath: $storagePath,
                );

                /** @var Model $file */
                $file = Model::query()->make($fileVO->toArray());
                $file->appendToNode($parentFolder)
                    ->save();

                $models->add($file);
            } elseif (is_array($item)) {
                $folderVO = new FileFolderVO($key);
                /** @var Model $folder */
                $folder = Model::query()->make($folderVO->toArray());
                $parentFolder->appendNode($folder);

                $this->make($folder, $item, $models);
            }
        }

        return $models;
    }
}
