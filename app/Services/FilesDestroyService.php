<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FilesDestroyServiceInterface;
use App\Dto\DestroyFileFromStorageDto;
use App\Models\File;
use Illuminate\Database\Eloquent\Collection;

final readonly class FilesDestroyService implements FilesDestroyServiceInterface
{
    /**
     * @param  Collection<File>  $collection
     * @return \Illuminate\Support\Collection<DestroyFileFromStorageDto>
     */
    public function destroy(Collection $collection): \Illuminate\Support\Collection
    {
        $filteredCollection = $collection->whereInstanceOf(File::class);

        if ($filteredCollection->count() === 0) {
            return collect();
        }

        $filesForDelete = collect();

        foreach ($filteredCollection as $file) {
            if ($file->isFolder()) {
                $filesForDelete->push(
                    ...$file->descendants()->get()->filter(fn (File $f) => ! $f->isFolder())
                );
            } else {
                $filesForDelete->push($file);
            }
        }

        $collectionDto = collect();

        foreach ($filesForDelete as $file) {
            $dto = new DestroyFileFromStorageDto(
                disk: $file->disk,
                fileStoragePath: $file->storage_path
            );

            $collectionDto->push($dto);
        }

        $filteredCollection->each(fn (File $file) => $file->forceDelete());

        return $collectionDto;
    }
}
