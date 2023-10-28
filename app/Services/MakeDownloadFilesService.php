<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FilesArchiveInterface;
use App\Contracts\GetFileContentInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Models\File;
use App\Services\Exceptions\DownloadEmptyFolderException;
use App\Services\Exceptions\OpenArchiveException;
use App\Services\Exceptions\PutFileForDownloadException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

readonly class MakeDownloadFilesService
{
    public function __construct(
        private StorageLocalServiceInterface $localService,
        private GetFileContentInterface $fileContent,
        private FilesArchiveInterface $archive,
    ) {
    }

    /**
     * @param  Collection<File>|BaseCollection<File>  $files
     * @return string Full path to download file
     *
     * @throws Throwable|OpenArchiveException|DownloadEmptyFolderException|RuntimeException
     */
    public function handle(BaseCollection|Collection $files): string
    {
        throw_if($files->isEmpty(), message: 'No files for download');

        if ($files->count() === 1) {
            /** @var File $file */
            $file = $files->first();

            throw_if(
                $file->isFolder() && $file->children()->count() === 0,
                DownloadEmptyFolderException::class,
                message: 'Folder "'.$files->first()->name.'" is empty',
            );

            if (! $file->isFolder()) {
                $storageFileName = Str::random(32);
                $content = $this->fileContent->getContent($file);

                throw_unless(
                    $this->localService->filesystem()->put($storageFileName, $content),
                    PutFileForDownloadException::class,
                    message: 'Can not put file to temporary storage'
                );

                return $this->localService->filesystem()->path($storageFileName);
            }
        }

        return $this->archive->addFiles($files);
    }
}
