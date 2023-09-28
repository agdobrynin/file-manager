<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\StorageServiceInterface;
use App\Dto\DownloadFileDto;
use App\Enums\DiskEnum;
use App\Models\File;
use App\Services\Exceptions\DownloadEmptyFolderException;
use App\Services\Exceptions\OpenArchiveException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

readonly class MakeDownloadFiles
{
    public function __construct(
        private StorageCloudServiceInterface $cloudService,
        private StorageLocalServiceInterface $localService,
        private ZipArchive                   $archive
    )
    {
    }

    /**
     * @param Collection<File> $files
     * @throws Throwable|OpenArchiveException|DownloadEmptyFolderException|RuntimeException
     */
    public function handle(Collection $files): DownloadFileDto
    {
        throw_if($files->isEmpty(), message: 'No files for download');

        if ($files->count() === 1) {
            /** @var File $file */
            $file = $files->first();

            throw_if(
                $file->isFolder() && $file->children()->count() === 0,
                DownloadEmptyFolderException::class,
                message: 'Folder "' . $files->first()->name . '" is empty',
            );

            if (!$file->isFolder()) {
                $storageFileName = Str::random(32);

                throw_unless($this->localService->filesystem()->put($storageFileName, $this->getContent($file)));

                $storagePath = $this->localService->filesystem()->path($storageFileName);

                return new DownloadFileDto($file->name, $storagePath);
            }
        }

        $storagePath = $this->localService->filesystem()->path(Str::random(32) . '.zip');

        throw_unless(
            $this->archive->open($storagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE),
            OpenArchiveException::class
        );

        $this->addToZip($files);
        $this->archive->close();

        /** @var File $file */
        $file = $files->first();

        if ($files->count() === 1 && $file->isFolder()) {
            $realFileName = $file->name;
        } else {
            $realFileName = $file->parent->isRoot()
                ? $file->user->name
                : $file->parent->name;
        }

        return new DownloadFileDto($realFileName . '.zip', $storagePath);
    }

    private function getContent(File $file): string
    {
        /** @var StorageServiceInterface $storage */
        $storage = match ($file->disk) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
        };

        return $storage->filesystem()->get($file->storage_path);
    }

    /**
     * @param Collection<File> $files
     */
    private function addToZip(Collection $files, string $ancestors = ''): void
    {
        foreach ($files as $file) {
            if ($file->isFolder() && $file->children()->count()) {
                $this->addToZip($file->children()->get(), $ancestors . $file->name . DIRECTORY_SEPARATOR);
            } else if (!$file->isFolder()) {
                $filePath = $ancestors . $file->name;

                $this->archive->addFromString($filePath, $this->getContent($file));
            }
        }
    }
}
