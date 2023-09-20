<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MoveFileBetweenStorageInterface;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

class MoveFileToCloud implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly File $file)
    {
        $this->onQueue('upload');
    }

    public function middleware(): array
    {
        return [new RateLimited(self::class)];
    }

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(MoveFileBetweenStorageInterface $moveFileBetweenStorage): void
    {
        $moveFileBetweenStorage->move($this->file);
    }
}
