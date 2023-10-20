<?php
declare(strict_types=1);

namespace App\VO;

use App\Models\File;
use App\VO\Exception\DownloadFileCollectionEmpty;
use App\VO\Exception\DownloadFileNotFound;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

readonly class DownloadFileVO
{
    public string $fileName;
    public string $downloadFile;

    /**
     * @throws RuntimeException|Throwable
     */
    public function __construct(Collection $files, string $downloadFile, string $defaultFileName)
    {
        $filteredFiles = $files->whereInstanceOf(File::class);

        throw_if(
            $filteredFiles->isEmpty(),
            exception: DownloadFileCollectionEmpty::class,
            message: 'Collection of files is empty'
        );

        throw_unless(
            is_file($downloadFile),
            exception: DownloadFileNotFound::class,
            message: 'File ' . $downloadFile . ' not found'
        );

        $this->downloadFile = $downloadFile;

        /** @var File $firstItem */
        $firstItem = $files->first();

        if ($files->count() === 1 && !$firstItem->isFolder()) {
            $fileName = $firstItem->name;
        } else {
            $extension = pathinfo($downloadFile, PATHINFO_EXTENSION);

            if ($files->count() === 1) {
                $fileName = $firstItem->name . '.' . $extension;
            } else {
                $fileName = $defaultFileName . '.' . $extension;
            }
        }

        $this->fileName = $fileName;
    }
}