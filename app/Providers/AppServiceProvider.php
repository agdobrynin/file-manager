<?php

namespace App\Providers;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Contracts\StorageCloudServiceInterface;
use App\Contracts\StorageLocalServiceInterface;
use App\Contracts\UploadTreeFilesServiceInterface;
use App\Enums\DiskEnum;
use App\Jobs\MoveFileToCloud;
use App\Services\MoveFileBetweenStorage;
use App\Services\StorageService;
use App\Services\UploadTreeFilesService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            StorageLocalServiceInterface::class,
            fn() => new StorageService(Storage::disk(DiskEnum::LOCAL->value), DiskEnum::LOCAL)
        );

        $this->app->bind(
            StorageCloudServiceInterface::class,
            fn() => new StorageService(Storage::disk(DiskEnum::CLOUD->value), DiskEnum::CLOUD)
        );

        $this->app->bind(
            UploadTreeFilesServiceInterface::class,
            fn() => new UploadTreeFilesService($this->app->make(StorageLocalServiceInterface::class))
        );

        $this->app->bind(
            MoveFileBetweenStorageInterface::class,
            fn() => new MoveFileBetweenStorage(
                $this->app->make(StorageLocalServiceInterface::class),
                $this->app->make(StorageCloudServiceInterface::class)
            )
        );
    }

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
}
