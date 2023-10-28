<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Services\Exceptions\FilesCollectionIsEmpty;
use App\Services\Exceptions\OpenArchiveException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Throwable;

/**
 * Make file archive for download by http.
 * Archive file must be located in local file system.
 */
interface FilesArchiveInterface
{
    /**
     * Return file name for download and absolute path to file.
     *
     * @return string Fill path to archive file
     *
     * @throws OpenArchiveException|FilesCollectionIsEmpty|Throwable
     */
    public function addFiles(BaseCollection|Collection $files): string;
}
