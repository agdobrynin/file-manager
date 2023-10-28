<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FilesArchiveInterface;
use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Services\Exceptions\FilesCollectionIsEmpty;
use App\Services\Exceptions\OpenArchiveException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

readonly class FilesArchive implements FilesArchiveInterface
{
    public function __construct(
        private ZipArchive $archive,
        private StorageLocalServiceInterface $localService,
        private GetFileContentInterface $fileContent,
    ) {
    }

    /**
     * @return string Full path to archive file
     *
     * @throws OpenArchiveException|FilesCollectionIsEmpty|Throwable
     */
    public function addFiles(Collection|BaseCollection $files): string
    {
        throw_unless($files->count(), exception: FilesCollectionIsEmpty::class);

        $storagePath = $this->localService->filesystem()->path(Str::random(32).'.zip');
        $zipIsOpen = $this->archive->open($storagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        throw_unless($zipIsOpen, OpenArchiveException::class);

        $addToZip = function ($files, $ancestors = '') use (&$addToZip): void {
            foreach ($files as $file) {
                if ($file->isFolder() && $file->children()->count()) {
                    $addToZip($file->children()->get(), $ancestors.$file->name.DIRECTORY_SEPARATOR);
                } elseif (! $file->isFolder()) {
                    $filePath = $ancestors.$file->name;
                    $content = $this->fileContent->getContent($file);
                    $this->archive->addFromString($filePath, $content);
                }
            }
        };

        $addToZip($files);

        $this->archive->close();

        return $storagePath;
    }
}
