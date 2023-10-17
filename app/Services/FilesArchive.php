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

        $filesToZip = collect();

        /** @var File $file */
        foreach ($files as $file) {
            if ($file->isFolder()) {
                $subFiles = $file->descendants()
                    ->withDepth()
                    ->get()
                    ->filter(fn(File $file) => !$file->isFolder());
                $filesToZip->push(...$subFiles->all());
            } else {
                $filesToZip->push($file);
            }
        }

        /** @var Collection<File> $filesSorted */
        $filesSorted = $filesToZip->sortBy('depth');
        $partOfAbsolutePath = dirname($filesSorted->first()->path);

        foreach ($filesSorted as $file) {
            $content = $this->fileContent->getContent($file);
            $pathInArchive = Str::replaceFirst($partOfAbsolutePath, '', $file->path);
            $this->archive->addFromString(ltrim($pathInArchive, DIRECTORY_SEPARATOR), $content);
        }

        $this->archive->close();

        /** @var File $mainParent */
        $mainParent = $filesSorted->first()->parent;
        $realFileName = $mainParent->isRoot()
            ? $mainParent->user->name : $mainParent->name;

        return new DownloadFileDto($realFileName . '.zip', $storagePath);
    }
}
