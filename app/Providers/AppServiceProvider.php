<?php

namespace App\Providers;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Contracts\UploadTreeFilesServiceInterface;
use App\Enums\DiskEnum;
use App\Services\MoveFileBetweenStorage;
use App\Services\StorageService;
use App\Services\UploadTreeFilesService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UploadTreeFilesServiceInterface::class, function () {
            $local = new StorageService(Storage::disk(DiskEnum::LOCAL->value), DiskEnum::LOCAL);

            return new UploadTreeFilesService($local);
        });

        $this->app->bind(MoveFileBetweenStorageInterface::class, function () {
            $local = new StorageService(Storage::disk(DiskEnum::LOCAL->value), DiskEnum::LOCAL);
            $cloud = new StorageService(Storage::disk(DiskEnum::CLOUD->value), DiskEnum::CLOUD);

            return new MoveFileBetweenStorage($local, $cloud);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
