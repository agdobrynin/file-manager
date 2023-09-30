<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Dto\DestroyFileFromStorageDto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteFileFromStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;

    public int $backoff = 10;

    public function __construct(protected DestroyFileFromStorageDto $dto)
    {
    }

    public function handle(StorageByDiskTypeServiceInterface $storageByModelService): void
    {
        $storageByModelService->resolve($this->dto->disk)
            ->filesystem()
            ->delete($this->dto->fileStoragePath);
    }
}
