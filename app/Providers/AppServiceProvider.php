<?php

namespace App\Providers;

use App\Contracts\FilesArchiveInterface;
use App\Contracts\FilesDestroyServiceInterface;
use App\Contracts\GetFileContentInterface;
use App\Contracts\MoveFileBetweenStorageInterface;
use App\Contracts\StorageByDiskTypeServiceInterface;
use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\UploadTreeFilesServiceInterface;
use App\Enums\DiskEnum;
use App\Jobs\MoveFileToCloud;
use App\Services\FilesArchive;
use App\Services\FilesDestroyService;
use App\Services\GetFileContent;
use App\Services\MoveFileBetweenStorage;
use App\Services\StorageByDiskTypeService;
use App\Services\StorageService;
use App\Services\UploadTreeFilesService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for(MoveFileToCloud::class, static function (object $job) {
            [
                'decay_minutes' => $decayMinutes,
                'max_attempts' => $maxAttempts
            ] = config('upload_files.move_to_cloud');

            /** @var MoveFileToCloud $job */
            return Limit::perMinutes($decayMinutes ?? 1, $maxAttempts ?? 6)
                ->by($job->file->created_by);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            StorageLocalServiceInterface::class,
            fn () => new StorageService(Storage::disk(DiskEnum::LOCAL->value), DiskEnum::LOCAL)
        );

        $this->app->singleton(
            StorageCloudServiceInterface::class,
            fn () => new StorageService(Storage::disk(DiskEnum::CLOUD->value), DiskEnum::CLOUD)
        );

        $this->app->singleton(
            UploadTreeFilesServiceInterface::class,
            fn () => new UploadTreeFilesService($this->app->make(StorageLocalServiceInterface::class))
        );

        $this->app->singleton(
            MoveFileBetweenStorageInterface::class,
            fn () => new MoveFileBetweenStorage(
                $this->app->make(StorageLocalServiceInterface::class),
                $this->app->make(StorageCloudServiceInterface::class)
            )
        );

        $this->app->singleton(
            StorageByDiskTypeServiceInterface::class,
            StorageByDiskTypeService::class
        );

        $this->app->singleton(
            GetFileContentInterface::class,
            GetFileContent::class,
        );

        $this->app->singleton(
            FilesArchiveInterface::class,
            FilesArchive::class,
        );

        $this->app->singleton(
            FilesDestroyServiceInterface::class,
            FilesDestroyService::class
        );

        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }
}
