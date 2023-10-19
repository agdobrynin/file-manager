<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\FilesArchiveInterface;
use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Dto\DownloadFileDto;
use App\Models\File;
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
        private ZipArchive                   $archive,
        private StorageLocalServiceInterface $localService,
        private GetFileContentInterface      $fileContent,
    )
    {
    }

    /**
     * @param BaseCollection|Collection $files
     * @return DownloadFileDto
     * @throws OpenArchiveException|FilesCollectionIsEmpty|Throwable
     */
    public function addFiles(Collection|BaseCollection $files): DownloadFileDto
    {
        throw_unless($files->count(), exception: FilesCollectionIsEmpty::class);

        $storagePath = $this->localService->filesystem()->path(Str::random(32) . '.zip');
        $zipIsOpen = $this->archive->open($storagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        throw_unless($zipIsOpen, OpenArchiveException::class);

        $addToZip = function ($files, $ancestors = '') use (&$addToZip) {
            foreach ($files as $file) {
                if ($file->isFolder() && $file->children()->count()) {
                    $addToZip($file->children()->get(), $ancestors . $file->name . DIRECTORY_SEPARATOR);
                } else if (!$file->isFolder()) {
                    $filePath = $ancestors . $file->name;
                    $content = $this->fileContent->getContent($file);
                    $this->archive->addFromString($filePath, $content);
                }
            }
        };

        $addToZip($files);

        $this->archive->close();

        /** @var File $mainParent */
        $mainParent = $files->first()->parent;
        $realFileName = $mainParent->isRoot()
            ? $mainParent->user->name
            : $mainParent->name;

        return new DownloadFileDto($realFileName . '.zip', $storagePath);
    }
}
