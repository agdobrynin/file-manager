<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Dto\DestroyFileFromStorageDto;
use App\Models\File;
use Illuminate\Database\Eloquent\Collection;

interface FilesDestroyServiceInterface
{
    /**
     * Force delete models.
     * Returned collection may be run in queue.
     *
     * @param  Collection<File>  $collection
     * @return \Illuminate\Support\Collection<DestroyFileFromStorageDto>
     */
    public function destroy(Collection $collection): \Illuminate\Support\Collection;
}
