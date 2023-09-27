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
                $fileName = Str::random(32);

                throw_unless($this->localService->filesystem()->put($fileName, $this->getContent($file)));

                $path = $this->localService->filesystem()->path($fileName);

                return new DownloadFileDto($file->name, $path);
            }
        }

        $fileName = Str::random(32) . '.zip';
        $filePath = $this->localService->filesystem()->path($fileName);

        throw_unless(
            $this->archive->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE),
            OpenArchiveException::class
        );

        $this->addToZip($files);
        $this->archive->close();

        return new DownloadFileDto($fileName, $filePath);
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

    private function getContent(File $file): string
    {
        /** @var StorageServiceInterface $storage */
        $storage = match ($file->disk) {
            DiskEnum::LOCAL => $this->localService,
            DiskEnum::CLOUD => $this->cloudService,
        };

        return $storage->filesystem()->get($file->path);
    }
}
